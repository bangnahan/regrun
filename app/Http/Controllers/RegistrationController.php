<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\Participant;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\MailketingService;
use App\Services\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    protected TripayService $tripayService;
    protected MailketingService $mailketingService;

    public function __construct(TripayService $tripayService, MailketingService $mailketingService)
    {
        $this->tripayService = $tripayService;
        $this->mailketingService = $mailketingService;
    }

    /**
     * Step 1: Select tickets and quantities
     */
    public function index(Request $request, ?string $slug = null)
    {
        $event = Event::getActiveEvent($slug, $request->getHost());

        if (!$event) {
            abort(404, 'Event lari tidak ditemukan atau pendaftaran belum dibuka.');
        }

        $categories = $event->ticketCategories()->where('is_active', true)->get();

        return view('registration.step1_tickets', compact('event', 'categories'));
    }

    /**
     * Step 2: Input participant details for each selected ticket
     */
    public function stepParticipants(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'tickets' => 'required|array',
        ]);

        $event = Event::findOrFail($request->event_id);
        $ticketsInput = $request->tickets; // [category_id => quantity]

        $selectedTickets = [];
        $totalQuantity = 0;

        foreach ($ticketsInput as $categoryId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $category = TicketCategory::where('event_id', $event->id)->findOrFail($categoryId);

                // Verify quota availability
                if ($category->remaining_quota < $qty) {
                    return back()->with('error', "Maaf, sisa kuota untuk {$category->name} hanya tersisa {$category->remaining_quota} tiket.");
                }

                $selectedTickets[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_code' => $category->code,
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'code' => $category->code,
                    ],
                    'quantity' => $qty,
                    'price' => $category->current_price,
                ];
                $totalQuantity += $qty;
            }
        }

        if ($totalQuantity <= 0) {
            return back()->with('error', 'Silakan pilih minimal 1 tiket untuk melanjutkan.');
        }

        // Save selected tickets in session
        session(['registration_tickets' => $selectedTickets, 'registration_event_id' => $event->id]);

        $jerseySizes = JerseySize::where('is_available', true)->orderBy('sort_order')->get();

        return view('registration.step2_participants', compact('event', 'selectedTickets', 'totalQuantity', 'jerseySizes'));
    }

    /**
     * Step 3: Checkout and payment method selection
     */
    public function stepCheckout(Request $request)
    {
        $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*.full_name' => 'required|string|max:120',
            'participants.*.identity_number' => 'required|string|max:50',
            'participants.*.gender' => 'required|in:L,P',
            'participants.*.date_of_birth' => 'required|date',
            'participants.*.phone_number' => 'required|string|max:30',
            'participants.*.email' => 'required|email|max:150',
            'participants.*.jersey_size_id' => 'required|exists:jersey_sizes,id',
            'participants.*.blood_type' => 'required|string',
            'participants.*.bib_name' => 'required|string|max:12',
            'participants.*.emergency_contact_name' => 'required|string|max:100',
            'participants.*.emergency_contact_phone' => 'required|string|max:30',
            'participants.*.emergency_contact_relation' => 'required|string|max:50',
        ]);

        $eventId = session('registration_event_id');
        $selectedTickets = session('registration_tickets');

        if (!$eventId || empty($selectedTickets)) {
            return redirect()->route('register.index')->with('error', 'Sesi pendaftaran Anda telah berakhir. Silakan pilih tiket kembali.');
        }

        $event = Event::findOrFail($eventId);
        $participantsData = $request->participants;

        // Save participant details into session
        session(['registration_participants' => $participantsData]);

        // Calculate totals
        $subtotal = 0;
        foreach ($selectedTickets as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }

        // Get Tripay payment channels
        $paymentChannels = $this->tripayService->getPaymentChannels();

        return view('registration.step3_checkout', compact('event', 'selectedTickets', 'participantsData', 'subtotal', 'paymentChannels'));
    }

    /**
     * Step 4: Process transaction & redirect to Tripay
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'buyer_name' => 'required|string|max:120',
            'buyer_email' => 'required|email|max:150',
            'buyer_phone' => 'required|string|max:30',
            'payment_method' => 'required|string',
            'agree_terms' => 'accepted',
        ]);

        $eventId = session('registration_event_id');
        $selectedTickets = session('registration_tickets');
        $participantsData = session('registration_participants');

        if (!$eventId || empty($selectedTickets) || empty($participantsData)) {
            return redirect()->route('register.index')->with('error', 'Sesi telah kedaluwarsa. Silakan mulai kembali.');
        }

        $event = Event::findOrFail($eventId);

        // Execute in database transaction with pessimistic locking
        $transaction = DB::transaction(function () use ($event, $selectedTickets, $participantsData, $request) {
            $subtotal = 0;

            // 1. Lock categories and check quota
            foreach ($selectedTickets as $item) {
                $catId = $item['category_id'] ?? (is_array($item['category']) ? ($item['category']['id'] ?? null) : ($item['category']->id ?? null));
                $category = TicketCategory::where('id', $catId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($category->remaining_quota < $item['quantity']) {
                    throw new \Exception("Maaf, kuota untuk tiket {$category->name} tidak mencukupi.");
                }

                // Increment reserved count
                $category->increment('reserved_count', $item['quantity']);
                $subtotal += ($item['price'] * $item['quantity']);
            }

            // Estimate admin fee (default Tripay fee flat 4.500)
            $feeAmount = 4500;
            $grandTotal = $subtotal + $feeAmount;

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $merchantRef = 'TRX-' . time() . '-' . Str::random(4);

            // 2. Create Transaction
            $trx = Transaction::create([
                'event_id' => $event->id,
                'invoice_number' => $invoiceNumber,
                'tripay_merchant_ref' => $merchantRef,
                'buyer_name' => $request->buyer_name,
                'buyer_email' => $request->buyer_email,
                'buyer_phone' => $request->buyer_phone,
                'subtotal' => $subtotal,
                'fee_amount' => $feeAmount,
                'grand_total' => $grandTotal,
                'payment_method' => $request->payment_method,
                'payment_channel_code' => $request->payment_method,
                'status' => 'UNPAID',
                'expires_at' => now()->addMinutes(60),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // 3. Create Transaction Items
            foreach ($selectedTickets as $item) {
                $catId = $item['category_id'] ?? (is_array($item['category']) ? ($item['category']['id'] ?? null) : ($item['category']->id ?? null));
                TransactionItem::create([
                    'transaction_id' => $trx->id,
                    'ticket_category_id' => $catId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            // 4. Create Participants
            $participantIndex = 0;
            foreach ($selectedTickets as $item) {
                $catId = $item['category_id'] ?? (is_array($item['category']) ? ($item['category']['id'] ?? null) : ($item['category']->id ?? null));
                for ($i = 0; $i < $item['quantity']; $i++) {
                    $pData = $participantsData[$participantIndex] ?? [];
                    [$ticketCode, $qrCodeHash] = Participant::generateUniqueCodes();

                    Participant::create([
                        'transaction_id' => $trx->id,
                        'ticket_category_id' => $catId,
                        'jersey_size_id' => $pData['jersey_size_id'],
                        'ticket_code' => $ticketCode,
                        'full_name' => $pData['full_name'],
                        'identity_number' => $pData['identity_number'],
                        'gender' => $pData['gender'],
                        'date_of_birth' => $pData['date_of_birth'],
                        'phone_number' => $pData['phone_number'],
                        'email' => $pData['email'],
                        'blood_type' => $pData['blood_type'],
                        'bib_name' => strtoupper($pData['bib_name']),
                        'emergency_contact_name' => $pData['emergency_contact_name'],
                        'emergency_contact_phone' => $pData['emergency_contact_phone'],
                        'emergency_contact_relation' => $pData['emergency_contact_relation'],
                        'medical_notes' => $pData['medical_notes'] ?? null,
                        'running_club' => $pData['running_club'] ?? null,
                        'qr_code_hash' => $qrCodeHash,
                    ]);

                    $participantIndex++;
                }
            }

            return $trx;
        });

        // 5. Call Tripay to generate closed transaction
        $tripayResult = $this->tripayService->createTransaction($transaction, $request->payment_method);

        if (empty($tripayResult['success'])) {
            return back()->with('error', 'Gagal menghubungi Tripay: ' . ($tripayResult['message'] ?? 'Silakan coba metode pembayaran lain atau hubungi panitia.'));
        }

        $transaction->update([
            'tripay_reference' => $tripayResult['reference'] ?? null,
            'tripay_checkout_url' => $tripayResult['checkout_url'] ?? null,
            'tripay_pay_code' => $tripayResult['pay_code'] ?? null,
            'tripay_qr_url' => $tripayResult['qr_url'] ?? null,
            'fee_amount' => $tripayResult['fee'] ?? 0,
            'grand_total' => $tripayResult['amount'] ?? ($transaction->subtotal + ($tripayResult['fee'] ?? 0)),
            'expires_at' => $tripayResult['expires_at'] ?? now()->addMinutes(120),
        ]);

        // Send Pending Payment / Invoice notification email via Mailketing
        try {
            $this->mailketingService->sendPendingPaymentEmail($transaction);
        } catch (\Throwable $e) {
            Log::warning('Mailketing pending payment email failed: ' . $e->getMessage());
        }

        // Clear registration sessions
        session()->forget(['registration_tickets', 'registration_participants', 'registration_event_id']);

        // Redirect to Tripay hosted checkout page if available
        if (!empty($tripayResult['checkout_url']) && empty($tripayResult['is_mock'])) {
            return redirect()->away($tripayResult['checkout_url']);
        }

        return redirect()->route('order.show', ['invoice' => $transaction->invoice_number]);
    }
}
