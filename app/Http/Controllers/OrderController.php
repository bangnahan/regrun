<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MailketingService;
use App\Services\TripayService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected TripayService $tripayService;
    protected MailketingService $mailketingService;

    public function __construct(TripayService $tripayService, MailketingService $mailketingService)
    {
        $this->tripayService = $tripayService;
        $this->mailketingService = $mailketingService;
    }

    /**
     * Display order status and instructions / E-Ticket
     */
    public function show(Request $request, string $invoice)
    {
        $transaction = Transaction::with(['event', 'items.ticketCategory', 'participants.jerseySize', 'participants.ticketCategory'])
            ->where('invoice_number', $invoice)
            ->firstOrFail();

        // Multi-domain tenant isolation: Dedicated domain cannot access another event's transaction
        $currentEvent = $request->attributes->get('currentEvent') ?? (app()->bound('currentEvent') ? app('currentEvent') : null);
        if ($currentEvent && (int) $transaction->event_id !== (int) $currentEvent->id) {
            $cleanHost = \App\Models\EventDomain::normalizeDomain($request->getHost());
            $isDedicatedDomain = \App\Models\EventDomain::where('domain', $cleanHost)->exists()
                || \App\Models\Event::where('custom_domain', $cleanHost)->exists();

            if ($isDedicatedDomain) {
                abort(404, 'Pesanan tidak ditemukan pada portal event ini.');
            }
        }

        $isSandbox = $this->tripayService->isSandbox() && !app()->environment('production');

        return view('order.show', compact('transaction', 'isSandbox'));
    }

    /**
     * Check status or simulate instant payment for testing Sandbox
     */
    public function simulatePay(Request $request, string $invoice)
    {
        // CRITICAL SECURITY ENFORCEMENT: Never permit simulated payment in production mode or production environment!
        if (!$this->tripayService->isSandbox() || app()->environment('production')) {
            abort(403, 'Aksi simulasi pembayaran dinonaktifkan di mode Produksi demi keamanan transaksi.');
        }

        $transaction = Transaction::where('invoice_number', $invoice)->firstOrFail();

        // Multi-domain tenant isolation: Dedicated domain cannot simulate/pay another event's transaction
        $currentEvent = $request->attributes->get('currentEvent') ?? (app()->bound('currentEvent') ? app('currentEvent') : null);
        if ($currentEvent && (int) $transaction->event_id !== (int) $currentEvent->id) {
            $cleanHost = \App\Models\EventDomain::normalizeDomain($request->getHost());
            $isDedicatedDomain = \App\Models\EventDomain::where('domain', $cleanHost)->exists()
                || \App\Models\Event::where('custom_domain', $cleanHost)->exists();

            if ($isDedicatedDomain) {
                abort(404, 'Pesanan tidak ditemukan pada portal event ini.');
            }
        }

        if ($transaction->status === 'PAID') {
            return back()->with('info', 'Transaksi ini sudah lunas.');
        }

        // Mark as paid
        $transaction->update([
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        // Shift quota from reserved to sold & generate BIB numbers
        foreach ($transaction->items as $item) {
            $cat = $item->ticketCategory;
            $cat->decrement('reserved_count', $item->quantity);
            $cat->increment('sold_count', $item->quantity);
        }

        // Assign BIB numbers to participants if auto_generate_bib is enabled
        $shouldAutoGenerate = $transaction->event?->auto_generate_bib ?? true;
        if ($shouldAutoGenerate) {
            foreach ($transaction->participants as $idx => $p) {
                if (!$p->bib_number) {
                    $codePrefix = substr($p->ticketCategory->code, 0, 2);
                    $p->update([
                        'bib_number' => $codePrefix . str_pad((string) $p->id, 4, '0', STR_PAD_LEFT),
                    ]);
                }
            }
        }

        // Trigger emails via Mailketing
        $this->mailketingService->sendInvoiceEmail($transaction);
        foreach ($transaction->participants as $participant) {
            $this->mailketingService->sendTicketEmail($participant);
        }

        return redirect()->route('order.show', ['invoice' => $transaction->invoice_number])
            ->with('success', 'Pembayaran berhasil dikonfirmasi! E-Ticket dan invoice telah dikirimkan.');
    }
}
