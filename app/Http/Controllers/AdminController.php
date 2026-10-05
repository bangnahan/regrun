<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\Participant;
use App\Models\SystemSetting;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Services\MailketingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    protected MailketingService $mailketingService;

    public function __construct(MailketingService $mailketingService)
    {
        $this->mailketingService = $mailketingService;
    }

    /**
     * Admin Dashboard Overview
     */
    public function dashboard(Request $request)
    {
        $eventId = $request->get('event_id');
        $events = Event::orderBy('race_date', 'desc')->get();

        $selectedEvent = $eventId ? Event::find($eventId) : Event::where('is_default', true)->first() ?? $events->first();

        $trxQuery = Transaction::query();
        $participantQuery = Participant::query();

        if ($selectedEvent) {
            $trxQuery->where('event_id', $selectedEvent->id);
            $participantQuery->whereHas('transaction', fn($q) => $q->where('event_id', $selectedEvent->id));
        }

        $totalRevenue = (clone $trxQuery)->where('status', 'PAID')->sum('subtotal');
        $paidTransactionsCount = (clone $trxQuery)->where('status', 'PAID')->count();
        $unpaidTransactionsCount = (clone $trxQuery)->where('status', 'UNPAID')->count();
        $expiredTransactionsCount = (clone $trxQuery)->where('status', 'EXPIRED')->count();

        $totalParticipants = (clone $participantQuery)->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))->count();

        // Ticket Categories quota & sales breakdown
        $categories = $selectedEvent ? $selectedEvent->ticketCategories : TicketCategory::all();

        // Recent Transactions
        $recentTransactions = (clone $trxQuery)->with('items.ticketCategory')->latest()->take(8)->get();

        return view('admin.dashboard', compact(
            'events',
            'selectedEvent',
            'totalRevenue',
            'paidTransactionsCount',
            'unpaidTransactionsCount',
            'expiredTransactionsCount',
            'totalParticipants',
            'categories',
            'recentTransactions'
        ));
    }

    /**
     * Modul Rekap Produksi Jersey Pabrik (Pivot Matrix XS - 5XL)
     */
    public function jerseyRecap(Request $request)
    {
        $eventId = $request->get('event_id');
        $events = Event::all();
        $selectedEvent = $eventId ? Event::find($eventId) : Event::where('is_default', true)->first() ?? $events->first();

        $sizes = JerseySize::orderBy('sort_order')->get();
        $categories = $selectedEvent ? $selectedEvent->ticketCategories : TicketCategory::all();

        // Build matrix data
        $matrix = [];
        $sizeTotals = array_fill_keys($sizes->pluck('size_code')->toArray(), 0);
        $overallTotal = 0;

        foreach ($categories as $cat) {
            $row = [
                'category_name' => $cat->name,
                'category_code' => $cat->code,
                'sizes' => [],
                'total' => 0,
            ];

            foreach ($sizes as $s) {
                $count = Participant::where('ticket_category_id', $cat->id)
                    ->where('jersey_size_id', $s->id)
                    ->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))
                    ->count();

                $row['sizes'][$s->size_code] = $count;
                $row['total'] += $count;
                $sizeTotals[$s->size_code] += $count;
                $overallTotal += $count;
            }

            $matrix[] = $row;
        }

        return view('admin.jersey_recap', compact('events', 'selectedEvent', 'sizes', 'matrix', 'sizeTotals', 'overallTotal'));
    }

    /**
     * Export Rekap Jersey Pabrik ke file CSV
     */
    public function exportJerseyCsv(Request $request): StreamedResponse
    {
        $eventId = $request->get('event_id');
        $event = Event::find($eventId) ?? Event::first();
        $sizes = JerseySize::orderBy('sort_order')->get();
        $categories = $event ? $event->ticketCategories : TicketCategory::all();

        $fileName = 'rekap_jersey_pabrik_' . ($event ? $event->slug : 'all') . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($categories, $sizes) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            // Header
            $headers = ['Kategori Lomba'];
            foreach ($sizes as $s) {
                $headers[] = $s->size_code;
            }
            $headers[] = 'TOTAL JERSEY';
            fputcsv($handle, $headers);

            $sizeTotals = array_fill_keys($sizes->pluck('size_code')->toArray(), 0);
            $grandTotal = 0;

            foreach ($categories as $cat) {
                $row = [$cat->name];
                $rowTotal = 0;
                foreach ($sizes as $s) {
                    $count = Participant::where('ticket_category_id', $cat->id)
                        ->where('jersey_size_id', $s->id)
                        ->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))
                        ->count();

                    $row[] = $count;
                    $rowTotal += $count;
                    $sizeTotals[$s->size_code] += $count;
                    $grandTotal += $count;
                }
                $row[] = $rowTotal;
                fputcsv($handle, $row);
            }

            // Footer row
            $footer = ['TOTAL KESELURUHAN'];
            foreach ($sizes as $s) {
                $footer[] = $sizeTotals[$s->size_code];
            }
            $footer[] = $grandTotal;
            fputcsv($handle, $footer);

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Transactions List
     */
    public function transactions(Request $request)
    {
        $query = Transaction::with(['event', 'items.ticketCategory', 'participants'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhere('buyer_name', 'like', "%{$s}%")
                    ->orWhere('buyer_email', 'like', "%{$s}%")
                    ->orWhere('buyer_phone', 'like', "%{$s}%")
                    ->orWhere('tripay_reference', 'like', "%{$s}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();
        $events = Event::all();

        return view('admin.transactions', compact('transactions', 'events'));
    }

    /**
     * Mark Transaction as PAID manually (by Admin)
     */
    public function markAsPaid(string $invoice)
    {
        $transaction = Transaction::where('invoice_number', $invoice)->firstOrFail();

        if ($transaction->status !== 'PAID') {
            $transaction->update([
                'status' => 'PAID',
                'paid_at' => now(),
            ]);

            foreach ($transaction->items as $item) {
                $cat = $item->ticketCategory;
                $cat->decrement('reserved_count', $item->quantity);
                $cat->increment('sold_count', $item->quantity);
            }

            foreach ($transaction->participants as $p) {
                if (!$p->bib_number) {
                    $codePrefix = substr($p->ticketCategory->code, 0, 2);
                    $p->update(['bib_number' => $codePrefix . str_pad((string) $p->id, 4, '0', STR_PAD_LEFT)]);
                }
            }

            $this->mailketingService->sendInvoiceEmail($transaction);
            foreach ($transaction->participants as $p) {
                $this->mailketingService->sendTicketEmail($p);
            }
        }

        return back()->with('success', "Transaksi {$invoice} berhasil diubah menjadi PAID dan email telah dikirim.");
    }

    /**
     * Resend E-Tickets and Invoice via Mailketing
     */
    public function resendEmail(string $invoice)
    {
        $transaction = Transaction::where('invoice_number', $invoice)->firstOrFail();

        $this->mailketingService->sendInvoiceEmail($transaction);
        foreach ($transaction->participants as $p) {
            $this->mailketingService->sendTicketEmail($p);
        }

        return back()->with('success', "Email invoice dan e-ticket untuk {$invoice} berhasil dikirim ulang ke Mailketing.");
    }

    /**
     * Participants Master List
     */
    public function participants(Request $request)
    {
        $query = Participant::with(['transaction.event', 'ticketCategory', 'jerseySize'])->latest();

        if ($request->filled('event_id')) {
            $query->whereHas('transaction', fn($q) => $q->where('event_id', $request->event_id));
        }

        if ($request->filled('category_id')) {
            $query->where('ticket_category_id', $request->category_id);
        }

        if ($request->filled('jersey_size_id')) {
            $query->where('jersey_size_id', $request->jersey_size_id);
        }

        if ($request->filled('rpc_status')) {
            $query->where('is_racepack_collected', $request->rpc_status === '1');
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('ticket_code', 'like', "%{$s}%")
                    ->orWhere('bib_number', 'like', "%{$s}%")
                    ->orWhere('bib_name', 'like', "%{$s}%")
                    ->orWhere('identity_number', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone_number', 'like', "%{$s}%");
            });
        }

        $participants = $query->paginate(25)->withQueryString();
        $events = Event::all();
        $categories = TicketCategory::all();
        $jerseySizes = JerseySize::orderBy('sort_order')->get();

        return view('admin.participants', compact('participants', 'events', 'categories', 'jerseySizes'));
    }

    /**
     * Toggle Racepack Collection status
     */
    public function toggleRacepack(int $participantId)
    {
        $participant = Participant::findOrFail($participantId);
        $newStatus = !$participant->is_racepack_collected;

        $participant->update([
            'is_racepack_collected' => $newStatus,
            'racepack_collected_at' => $newStatus ? now() : null,
            'racepack_collected_by' => $newStatus ? Auth::id() : null,
        ]);

        return back()->with('success', "Status pengambilan racepack untuk {$participant->full_name} berhasil diperbarui.");
    }

    /**
     * Export Participants CSV
     */
    public function exportParticipantsCsv(Request $request): StreamedResponse
    {
        $query = Participant::with(['transaction.event', 'ticketCategory', 'jerseySize'])
            ->whereHas('transaction', fn($q) => $q->where('status', 'PAID'))
            ->latest();

        if ($request->filled('event_id')) {
            $query->whereHas('transaction', fn($q) => $q->where('event_id', $request->event_id));
        }

        $participants = $query->get();
        $fileName = 'data_pelari_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($participants) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No Tiket',
                'No BIB',
                'Nama di BIB',
                'Nama Lengkap',
                'NIK / Paspor',
                'Gender',
                'Tgl Lahir',
                'Kategori',
                'Ukuran Jersey',
                'Golongan Darah',
                'WhatsApp',
                'Email',
                'Kontak Darurat',
                'No HP Darurat',
                'Hubungan Darurat',
                'Catatan Medis',
                'Komunitas',
                'Status RPC',
            ]);

            foreach ($participants as $p) {
                fputcsv($handle, [
                    $p->ticket_code,
                    $p->bib_number ?? '-',
                    $p->bib_name,
                    $p->full_name,
                    $p->identity_number,
                    $p->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                    $p->date_of_birth ? $p->date_of_birth->format('Y-m-d') : '',
                    $p->ticketCategory->name ?? '',
                    $p->jerseySize->size_code ?? '',
                    $p->blood_type,
                    $p->phone_number,
                    $p->email,
                    $p->emergency_contact_name,
                    $p->emergency_contact_phone,
                    $p->emergency_contact_relation,
                    $p->medical_notes ?? '-',
                    $p->running_club ?? '-',
                    $p->is_racepack_collected ? 'Sudah Diambil' : 'Belum Diambil',
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Manage Events & Quota / Early Bird
     */
    public function events()
    {
        $events = Event::with('ticketCategories')->latest()->get();
        return view('admin.events', compact('events'));
    }

    /**
     * Update Category Quota and Early Bird
     */
    public function updateCategory(Request $request, int $id)
    {
        $request->validate([
            'quota' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'early_bird_price' => 'nullable|numeric|min:0',
            'early_bird_end_date' => 'nullable|date',
            'is_active' => 'required|boolean',
        ]);

        $category = TicketCategory::findOrFail($id);
        $category->update([
            'quota' => $request->quota,
            'price' => $request->price,
            'early_bird_price' => $request->early_bird_price,
            'early_bird_end_date' => $request->early_bird_end_date,
            'is_active' => (bool) $request->is_active,
        ]);

        return back()->with('success', "Kategori {$category->name} berhasil diperbarui.");
    }

    /**
     * Settings Page (Tripay & Mailketing)
     */
    public function settings()
    {
        $settings = [
            'tripay_merchant_code' => SystemSetting::get('tripay_merchant_code', env('TRIPAY_MERCHANT_CODE', 'T39430')),
            'tripay_api_key' => SystemSetting::get('tripay_api_key', env('TRIPAY_API_KEY', '')),
            'tripay_private_key' => SystemSetting::get('tripay_private_key', env('TRIPAY_PRIVATE_KEY', '')),
            'tripay_sandbox' => SystemSetting::get('tripay_sandbox', env('TRIPAY_SANDBOX', true)),
            'mailketing_api_token' => SystemSetting::get('mailketing_api_token', env('MAILKETING_API_TOKEN', '')),
            'mailketing_sender_email' => SystemSetting::get('mailketing_sender_email', env('MAILKETING_SENDER_EMAIL', 'hi@jelatik.com')),
            'mailketing_sender_name' => SystemSetting::get('mailketing_sender_name', env('MAILKETING_SENDER_NAME', 'Panitia Event Lari')),
        ];

        return view('admin.settings', compact('settings'));
    }

    /**
     * Save Settings
     */
    public function saveSettings(Request $request)
    {
        $keys = [
            'tripay_merchant_code',
            'tripay_api_key',
            'tripay_private_key',
            'tripay_sandbox',
            'mailketing_api_token',
            'mailketing_sender_email',
            'mailketing_sender_name',
        ];

        foreach ($keys as $k) {
            if ($request->has($k)) {
                $group = str_starts_with($k, 'tripay') ? 'tripay' : 'mailketing';
                SystemSetting::set($k, $request->input($k), $group);
            }
        }

        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
