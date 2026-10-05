<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Participant;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailketingService
{
    protected string $apiToken;
    protected string $senderEmail;
    protected string $senderName;
    protected string $apiUrl = 'https://api.mailketing.co.id/api/v1/send';

    public function __construct()
    {
        $this->apiToken = SystemSetting::get('mailketing_api_token', config('services.mailketing.api_token', env('MAILKETING_API_TOKEN', '308b31d3313311776744479fa8fd7eb3')));
        $this->senderEmail = SystemSetting::get('mailketing_sender_email', config('services.mailketing.sender_email', env('MAILKETING_SENDER_EMAIL', 'hi@jelatix.com')));
        $this->senderName = SystemSetting::get('mailketing_sender_name', config('services.mailketing.sender_name', env('MAILKETING_SENDER_NAME', 'Panitia Event Lari')));
    }

    /**
     * Check Mailketing configuration status without sending email
     */
    public function checkStatus(): array
    {
        $hasToken = !empty($this->apiToken) && strlen($this->apiToken) >= 10;
        $hasSender = !empty($this->senderEmail) && filter_var($this->senderEmail, FILTER_VALIDATE_EMAIL);

        return [
            'connected' => $hasToken && $hasSender,
            'status' => ($hasToken && $hasSender) ? 'OK' : 'NOT_CONFIGURED',
            'sender_email' => $this->senderEmail,
            'sender_name' => $this->senderName,
            'message' => ($hasToken && $hasSender) ? 'API Token & Sender Email terkonfigurasi aktif.' : 'Token atau Sender Email belum diatur.',
        ];
    }

    /**
     * Test connection backwards-compatible wrapper.
     * When called without arguments, only checks status (does not flood recipient with emails).
     */
    public function testConnection(?string $testRecipient = null): array
    {
        if (empty($testRecipient)) {
            return $this->checkStatus();
        }

        return $this->sendSampleEmail($testRecipient, 'simple');
    }

    /**
     * Send realistic sample transactional email for previewing and testing
     */
    public function sendSampleEmail(string $recipientEmail, string $type = 'invoice'): array
    {
        $transaction = Transaction::with(['items.ticketCategory', 'participants.jerseySize', 'event'])->latest()->first();

        $subject = '';
        $htmlContent = '';
        $typeName = 'Invoice';

        switch ($type) {
            case 'pending_payment':
                $typeName = 'Tagihan Menunggu Pembayaran';
                if ($transaction) {
                    $subject = "[CONTOH] Tagihan Pendaftaran {$transaction->invoice_number} - " . ($transaction->event->title ?? 'Nusantara Sunset Run 2026');
                    $htmlContent = $this->buildPendingPaymentHtml($transaction);
                } else {
                    $subject = "[CONTOH] Tagihan Pendaftaran INV-SAMPLE-01 - Nusantara Sunset Run 2026";
                    $htmlContent = '<p>Contoh Tagihan Pembayaran</p>';
                }
                break;

            case 'eticket':
                $typeName = 'Official E-Ticket Pelari (QR Code)';
                $participant = $transaction?->participants?->first() ?? Participant::with(['transaction.event', 'ticketCategory', 'jerseySize'])->latest()->first();
                if ($participant) {
                    $subject = "[CONTOH E-Ticket] {$participant->full_name} ({$participant->ticket_code}) - " . ($participant->transaction->event->title ?? 'Nusantara Sunset Run 2026');
                    $htmlContent = $this->buildTicketHtml($participant);
                } else {
                    $subject = "[CONTOH E-Ticket] Budi Santoso (TKT-DEMO88) - Nusantara Sunset Run 2026";
                    $htmlContent = '<p>Contoh E-Ticket</p>';
                }
                break;

            case 'simple':
            case 'connection':
                $typeName = 'Tes Koneksi Sederhana';
                $subject = 'Tes Koneksi Mailketing API - RegRun';
                $htmlContent = '
                <div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #e2e8f0;border-radius:16px;background:#ffffff;">
                    <div style="font-size:24px;margin-bottom:12px;">✉️⚡</div>
                    <h3 style="color:#0f172a;margin:0 0 8px 0;font-size:18px;font-weight:800;">Koneksi Mailketing API Aktif &amp; Siap</h3>
                    <p style="color:#475569;font-size:13px;line-height:1.6;margin:0 0 16px 0;">Halo Admin, sistem registrasi lari RegRun berhasil terhubung dengan akun Mailketing Anda. Pengiriman email transaksional dan E-Ticket dengan QR Code siap berjalan.</p>
                    <div style="padding:12px;background:#f8fafc;border-radius:8px;font-size:12px;color:#64748b;">
                        Sender: <strong>' . htmlspecialchars($this->senderEmail) . '</strong> &bull; API Token: Terverifikasi
                    </div>
                </div>';
                break;

            case 'invoice':
            default:
                $typeName = 'Bukti Pembayaran Lunas (Invoice)';
                if ($transaction) {
                    $subject = "[CONTOH] Bukti Pembayaran Lunas {$transaction->invoice_number} - " . ($transaction->event->title ?? 'Nusantara Sunset Run 2026');
                    $htmlContent = $this->buildInvoiceHtml($transaction);
                } else {
                    $subject = "[CONTOH] Bukti Pembayaran Lunas INV-SAMPLE-01 - Nusantara Sunset Run 2026";
                    $htmlContent = '<p>Contoh Bukti Pembayaran Lunas</p>';
                }
                break;
        }

        try {
            $payload = [
                'api_token' => $this->apiToken,
                'from_email' => $this->senderEmail,
                'from_name' => $this->senderName,
                'recipient' => $recipientEmail,
                'recipient_name' => 'Tester RegRun',
                'subject' => $subject,
                'content' => $htmlContent,
            ];

            $response = Http::asForm()->timeout(15)->post($this->apiUrl, $payload);
            $resData = $response->json();

            if ($response->successful() && (($resData['status'] ?? '') === 'success' || stripos($resData['response'] ?? '', 'Mail Sent') !== false)) {
                return [
                    'connected' => true,
                    'status' => 'OK',
                    'type_name' => $typeName,
                    'message' => "Email contoh {$typeName} berhasil dikirim ke {$recipientEmail} via Mailketing.",
                ];
            }

            return [
                'connected' => false,
                'status' => 'FAILED',
                'type_name' => $typeName,
                'error' => $resData['response'] ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'status' => 'EXCEPTION',
                'type_name' => $typeName,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 1. Send Pending Payment Notification (UNPAID) upon Registration
     */
    public function sendPendingPaymentEmail(Transaction $transaction): bool
    {
        $transaction->loadMissing(['items.ticketCategory', 'participants.jerseySize', 'event']);
        $eventTitle = $transaction->event->title ?? 'Event Lari';
        $subject = "[Menunggu Pembayaran] Tagihan Pendaftaran {$transaction->invoice_number} - {$eventTitle}";

        $htmlContent = $this->buildPendingPaymentHtml($transaction);

        return $this->sendMail(
            $transaction->buyer_email,
            $transaction->buyer_name,
            $subject,
            $htmlContent,
            'pending_payment',
            $transaction->id,
            null
        );
    }

    /**
     * 2. Send Invoice / Payment Confirmation Email to Buyer (PAID)
     */
    public function sendInvoiceEmail(Transaction $transaction): bool
    {
        $transaction->loadMissing(['items.ticketCategory', 'participants.jerseySize', 'event']);
        $eventTitle = $transaction->event->title ?? 'Event Lari';
        $subject = "[Pembayaran Lunas] Bukti Pendaftaran {$transaction->invoice_number} - {$eventTitle}";

        $htmlContent = $this->buildInvoiceHtml($transaction);

        return $this->sendMail(
            $transaction->buyer_email,
            $transaction->buyer_name,
            $subject,
            $htmlContent,
            'invoice',
            $transaction->id,
            null
        );
    }

    /**
     * 3. Send Official E-Ticket with QR Code to each Runner Participant
     */
    public function sendTicketEmail(Participant $participant): bool
    {
        $participant->loadMissing(['transaction.event', 'ticketCategory', 'jerseySize']);
        $transaction = $participant->transaction;
        $event = $transaction->event ?? null;
        $eventTitle = $event->title ?? 'Event Lari';
        $subject = "[Official E-Ticket] {$participant->full_name} ({$participant->ticket_code}) - {$eventTitle}";

        $htmlContent = $this->buildTicketHtml($participant);

        return $this->sendMail(
            $participant->email,
            $participant->full_name,
            $subject,
            $htmlContent,
            'eticket',
            $transaction->id,
            $participant->id
        );
    }

    /**
     * Core dispatch to Mailketing API v1
     */
    protected function sendMail(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $htmlBody,
        string $type,
        ?int $transactionId = null,
        ?int $participantId = null
    ): bool {
        // Fast mock path for automated PHPUnit test environment
        if (app()->environment('testing')) {
            EmailLog::create([
                'transaction_id' => $transactionId,
                'participant_id' => $participantId,
                'recipient_email' => $recipientEmail,
                'email_type' => $type,
                'mailketing_message_id' => 'TEST-MK-' . time(),
                'status' => 'sent',
                'error_message' => null,
            ]);
            return true;
        }

        $status = 'failed';
        $errorMessage = null;
        $messageId = null;

        try {
            $payload = [
                'api_token' => $this->apiToken,
                'from_email' => $this->senderEmail,
                'from_name' => $this->senderName,
                'recipient' => $recipientEmail,
                'recipient_name' => $recipientName ?: $recipientEmail,
                'subject' => $subject,
                'content' => $htmlBody,
            ];

            // Mailketing API v1 requires x-www-form-urlencoded
            $response = Http::asForm()->timeout(15)->post($this->apiUrl, $payload);
            $resData = $response->json();

            if ($response->successful() && (($resData['status'] ?? '') === 'success' || stripos($resData['response'] ?? '', 'Mail Sent') !== false)) {
                $status = 'sent';
                $messageId = $resData['message_id'] ?? ('MK-' . uniqid());
                Log::info("Mailketing email successfully sent to {$recipientEmail} [{$type}]");
            } else {
                $errorMessage = $resData['response'] ?? $response->body();
                Log::warning("Mailketing API failed sending to {$recipientEmail}: {$errorMessage}");
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::error("Mailketing dispatch exception to {$recipientEmail}: {$errorMessage}");
        }

        // Record into email_logs for auditing in admin panel
        try {
            EmailLog::create([
                'transaction_id' => $transactionId,
                'participant_id' => $participantId,
                'recipient_email' => $recipientEmail,
                'email_type' => $type,
                'mailketing_message_id' => $messageId,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to record EmailLog: " . $e->getMessage());
        }

        return $status === 'sent';
    }

    /**
     * Build HTML Template for Pending Payment (UNPAID)
     */
    public function buildPendingPaymentHtml(Transaction $transaction): string
    {
        $transaction->loadMissing(['items.ticketCategory', 'participants.jerseySize', 'event']);
        $event = $transaction->event;
        $orderUrl = route('order.show', ['invoice' => $transaction->invoice_number]);
        $payUrl = $transaction->tripay_checkout_url ?: $orderUrl;

        $itemsHtml = '';
        foreach ($transaction->items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:12px;border-bottom:1px solid #f1f5f9;font-weight:600;color:#1e293b;font-size:13px;'>{$item->ticketCategory->name}</td>
                <td style='padding:12px;border-bottom:1px solid #f1f5f9;text-align:center;color:#64748b;font-size:13px;'>{$item->quantity}x</td>
                <td style='padding:12px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:700;color:#1e293b;font-size:13px;'>Rp " . number_format($item->subtotal, 0, ',', '.') . "</td>
            </tr>";
        }

        $participantsHtml = '';
        foreach ($transaction->participants as $idx => $p) {
            $num = $idx + 1;
            $participantsHtml .= "<tr style='font-size:12px;color:#334155;'>
                <td style='padding:8px 10px;border-bottom:1px solid #f1f5f9;'>#{$num} <strong>{$p->full_name}</strong></td>
                <td style='padding:8px 10px;border-bottom:1px solid #f1f5f9;'>{$p->ticketCategory->name}</td>
                <td style='padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:center;font-weight:bold;color:#ea580c;'>{$p->jerseySize->size_code}</td>
            </tr>";
        }

        $payCodeHtml = '';
        if (!empty($transaction->tripay_pay_code)) {
            $payCodeHtml = "
            <div style='margin:20px 0;padding:18px;background:#fffbeb;border:2px dashed #f59e0b;border-radius:14px;text-align:center;'>
                <div style='font-size:11px;font-weight:800;color:#b45309;text-transform:uppercase;letter-spacing:1px;'>KODE PEMBAYARAN / VIRTUAL ACCOUNT ({$transaction->payment_method})</div>
                <div style='font-size:26px;font-weight:900;color:#78350f;letter-spacing:3px;margin:10px 0;font-family:monospace;'>{$transaction->tripay_pay_code}</div>
                <div style='font-size:12px;color:#92400e;'>Silakan transfer sesuai nominal total tagihan tepat sampai 3 digit terakhir.</div>
            </div>";
        }

        $expiresAtText = $transaction->expires_at ? $transaction->expires_at->format('d M Y, H:i') . ' WIB' : '60 Menit';

        return "
        <div style='background-color:#f8fafc;padding:30px 10px;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;'>
            <div style='max-width:620px;margin:auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;box-shadow:0 10px 25px -5px rgba(0,0,0,0.06);'>
                <!-- Header -->
                <div style='background:linear-gradient(135deg,#ea580c,#c2410c);color:#ffffff;padding:32px 24px;text-align:center;'>
                    <span style='display:inline-block;padding:5px 14px;background:rgba(255,255,255,0.22);border-radius:20px;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;'>Pendaftaran Menunggu Pembayaran</span>
                    <h1 style='margin:12px 0 4px 0;font-size:24px;font-weight:900;letter-spacing:-0.5px;'>SELESAIKAN PEMBAYARAN</h1>
                    <p style='margin:0;font-size:14px;opacity:0.95;font-weight:500;'>{$event->title}</p>
                </div>

                <!-- Body Content -->
                <div style='padding:30px 24px;'>
                    <p style='font-size:15px;color:#1e293b;margin-top:0;line-height:1.6;'>
                        Halo <strong>{$transaction->buyer_name}</strong>,<br>
                        Pendaftaran tiket lomba Anda telah berhasil dibuat. Segera lakukan pembayaran sebelum batas waktu berakhir agar slot kuota tiket dan nomor BIB Anda tidak dibatalkan.
                    </p>

                    <!-- Meta Information Box -->
                    <table style='width:100%;margin:20px 0;background:#f8fafc;padding:16px;border-radius:14px;border:1px solid #e2e8f0;font-size:13px;border-collapse:separate;'>
                        <tr>
                            <td style='padding:5px 0;color:#64748b;width:40%;'>Nomor Invoice</td>
                            <td style='padding:5px 0;font-weight:800;color:#0f172a;font-family:monospace;'>{$transaction->invoice_number}</td>
                        </tr>
                        <tr>
                            <td style='padding:5px 0;color:#64748b;'>Metode Pembayaran</td>
                            <td style='padding:5px 0;font-weight:800;color:#ea580c;'>{$transaction->payment_method} (Tripay)</td>
                        </tr>
                        <tr>
                            <td style='padding:5px 0;color:#64748b;'>Batas Waktu Pembayaran</td>
                            <td style='padding:5px 0;font-weight:800;color:#dc2626;'>{$expiresAtText}</td>
                        </tr>
                    </table>

                    {$payCodeHtml}

                    <!-- Items Breakdown -->
                    <h3 style='margin:26px 0 12px 0;font-size:14px;font-weight:800;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;'>Rincian Biaya Pendaftaran</h3>
                    <table style='width:100%;border-collapse:collapse;font-size:13px;border:1px solid #f1f5f9;border-radius:12px;overflow:hidden;'>
                        <thead>
                            <tr style='background:#f8fafc;color:#475569;text-align:left;border-bottom:1px solid #e2e8f0;'>
                                <th style='padding:12px;font-weight:700;'>Kategori</th>
                                <th style='padding:12px;text-align:center;font-weight:700;'>Qty</th>
                                <th style='padding:12px;text-align:right;font-weight:700;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                            <tr>
                                <td colspan='2' style='padding:10px 12px;color:#64748b;text-align:right;font-size:12px;'>Biaya Layanan Payment Gateway:</td>
                                <td style='padding:10px 12px;text-align:right;color:#64748b;font-size:12px;'>Rp " . number_format($transaction->fee_amount, 0, ',', '.') . "</td>
                            </tr>
                            <tr style='background:#fff7ed;border-top:2px solid #fed7aa;'>
                                <td colspan='2' style='padding:14px 12px;font-weight:800;font-size:15px;color:#0f172a;text-align:right;'>TOTAL PEMBAYARAN:</td>
                                <td style='padding:14px 12px;font-weight:900;font-size:18px;color:#ea580c;text-align:right;'>Rp " . number_format($transaction->grand_total, 0, ',', '.') . "</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Participants Summary -->
                    <h3 style='margin:28px 0 10px 0;font-size:14px;font-weight:800;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;'>Daftar Pelari yang Didaftarkan</h3>
                    <table style='width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;'>
                        <thead>
                            <tr style='background:#f1f5f9;color:#475569;font-size:11px;text-transform:uppercase;'>
                                <th style='padding:8px 10px;text-align:left;'>Nama Pelari</th>
                                <th style='padding:8px 10px;text-align:left;'>Kategori</th>
                                <th style='padding:8px 10px;text-align:center;'>Jersey</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$participantsHtml}
                        </tbody>
                    </table>

                    <!-- Call To Action Button -->
                    <div style='margin:34px 0 24px 0;text-align:center;'>
                        <a href='{$payUrl}' style='display:inline-block;padding:16px 36px;background:#ea580c;color:#ffffff;text-decoration:none;font-weight:800;font-size:15px;border-radius:14px;box-shadow:0 6px 16px rgba(234,88,12,0.35);letter-spacing:0.3px;'>
                            Bayar Sekarang via Tripay &rarr;
                        </a>
                        <div style='margin-top:12px;font-size:12px;color:#94a3b8;'>
                            Atau lihat detail tagihan di: <a href='{$orderUrl}' style='color:#ea580c;text-decoration:none;font-weight:600;'>{$orderUrl}</a>
                        </div>
                    </div>

                    <!-- Helpful Note -->
                    <div style='padding:14px 16px;background:#f0fdf4;border-left:4px solid #16a34a;border-radius:10px;font-size:12px;color:#15803d;line-height:1.6;'>
                        <strong>✓ Verifikasi Otomatis:</strong> Pembayaran akan otomatis terdeteksi dalam hitungan detik. Setelah lunas, bukti pembayaran dan E-Ticket resmi pelari akan dikirimkan otomatis ke email masing-masing peserta.
                    </div>
                </div>

                <!-- Footer -->
                <div style='background:#f8fafc;padding:20px 24px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;line-height:1.5;'>
                    Email ini dikirimkan otomatis oleh sistem pendaftaran resmi <strong>{$event->title}</strong>.<br>
                    Jika Anda memiliki kendala pembayaran, silakan hubungi tim panitia.
                </div>
            </div>
        </div>";
    }

    /**
     * Build HTML Template for Invoice / Payment Receipt (PAID)
     */
    public function buildInvoiceHtml(Transaction $transaction): string
    {
        $transaction->loadMissing(['items.ticketCategory', 'participants.jerseySize', 'event']);
        $event = $transaction->event;
        $orderUrl = route('order.show', ['invoice' => $transaction->invoice_number]);

        $itemsHtml = '';
        foreach ($transaction->items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:12px;border-bottom:1px solid #f1f5f9;font-weight:600;color:#1e293b;font-size:13px;'>{$item->ticketCategory->name}</td>
                <td style='padding:12px;border-bottom:1px solid #f1f5f9;text-align:center;color:#64748b;font-size:13px;'>{$item->quantity}x</td>
                <td style='padding:12px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:700;color:#1e293b;font-size:13px;'>Rp " . number_format($item->subtotal, 0, ',', '.') . "</td>
            </tr>";
        }

        $participantsList = '';
        foreach ($transaction->participants as $idx => $p) {
            $num = $idx + 1;
            $bibText = $p->bib_number ? " &bull; Nomor BIB: <strong style='color:#ea580c;font-family:monospace;'>{$p->bib_number}</strong>" : '';
            $participantsList .= "<tr style='font-size:12px;color:#334155;'>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;'>
                    <strong style='color:#0f172a;font-size:13px;'>#{$num} {$p->full_name}</strong><br>
                    <span style='color:#64748b;font-size:11px;'>Nama BIB: {$p->bib_name}{$bibText}</span>
                </td>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;font-weight:600;'>{$p->ticketCategory->name}</td>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:center;font-weight:bold;color:#059669;'>{$p->jerseySize->size_code}</td>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;font-family:monospace;font-size:11px;color:#0284c7;font-weight:bold;'>{$p->ticket_code}</td>
            </tr>";
        }

        $paidAtText = $transaction->paid_at ? $transaction->paid_at->format('d M Y, H:i') . ' WIB' : now()->format('d M Y, H:i') . ' WIB';

        return "
        <div style='background-color:#f8fafc;padding:30px 10px;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;'>
            <div style='max-width:620px;margin:auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;box-shadow:0 10px 25px -5px rgba(0,0,0,0.06);'>
                <!-- Header -->
                <div style='background:linear-gradient(135deg,#059669,#047857);color:#ffffff;padding:32px 24px;text-align:center;'>
                    <span style='display:inline-block;padding:5px 14px;background:rgba(255,255,255,0.22);border-radius:20px;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;'>Bukti Transaksi Resmi</span>
                    <h1 style='margin:12px 0 4px 0;font-size:24px;font-weight:900;letter-spacing:-0.5px;'>PEMBAYARAN LUNAS</h1>
                    <p style='margin:0;font-size:14px;opacity:0.95;font-weight:500;'>{$event->title}</p>
                </div>

                <!-- Body -->
                <div style='padding:30px 24px;'>
                    <p style='font-size:15px;color:#1e293b;margin-top:0;line-height:1.6;'>
                        Halo <strong>{$transaction->buyer_name}</strong>,<br>
                        Terima kasih! Pembayaran pendaftaran Anda telah berhasil kami terima dan diverifikasi lunas oleh sistem Tripay Payment Gateway.
                    </p>

                    <!-- Status Card -->
                    <table style='width:100%;margin:20px 0;background:#f0fdf4;padding:16px;border-radius:14px;border:1px solid #bbf7d0;font-size:13px;border-collapse:separate;'>
                        <tr>
                            <td style='padding:5px 0;color:#166534;width:40%;'>Nomor Invoice</td>
                            <td style='padding:5px 0;font-weight:800;color:#0f172a;font-family:monospace;'>{$transaction->invoice_number}</td>
                        </tr>
                        <tr>
                            <td style='padding:5px 0;color:#166534;'>Status Pembayaran</td>
                            <td style='padding:5px 0;font-weight:900;color:#15803d;'>LUNAS (PAID) &#10003;</td>
                        </tr>
                        <tr>
                            <td style='padding:5px 0;color:#166534;'>Metode Pembayaran</td>
                            <td style='padding:5px 0;font-weight:800;color:#0f172a;'>{$transaction->payment_method}</td>
                        </tr>
                        <tr>
                            <td style='padding:5px 0;color:#166534;'>Waktu Pembayaran</td>
                            <td style='padding:5px 0;font-weight:700;color:#475569;'>{$paidAtText}</td>
                        </tr>
                    </table>

                    <!-- Items Breakdown -->
                    <h3 style='margin:26px 0 12px 0;font-size:14px;font-weight:800;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;'>Rincian Pembayaran</h3>
                    <table style='width:100%;border-collapse:collapse;font-size:13px;border:1px solid #f1f5f9;border-radius:12px;overflow:hidden;'>
                        <thead>
                            <tr style='background:#f8fafc;color:#475569;text-align:left;border-bottom:1px solid #e2e8f0;'>
                                <th style='padding:12px;font-weight:700;'>Kategori Lomba</th>
                                <th style='padding:12px;text-align:center;font-weight:700;'>Qty</th>
                                <th style='padding:12px;text-align:right;font-weight:700;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                            <tr>
                                <td colspan='2' style='padding:10px 12px;color:#64748b;text-align:right;font-size:12px;'>Biaya Layanan Gateway:</td>
                                <td style='padding:10px 12px;text-align:right;color:#64748b;font-size:12px;'>Rp " . number_format($transaction->fee_amount, 0, ',', '.') . "</td>
                            </tr>
                            <tr style='background:#f0fdf4;border-top:2px solid #bbf7d0;'>
                                <td colspan='2' style='padding:14px 12px;font-weight:800;font-size:15px;color:#0f172a;text-align:right;'>TOTAL LUNAS:</td>
                                <td style='padding:14px 12px;font-weight:900;font-size:18px;color:#059669;text-align:right;'>Rp " . number_format($transaction->grand_total, 0, ',', '.') . "</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Registered Runners Table -->
                    <h3 style='margin:28px 0 10px 0;font-size:14px;font-weight:800;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;'>Daftar Pelari Terdaftar</h3>
                    <table style='width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;'>
                        <thead>
                            <tr style='background:#f1f5f9;color:#475569;font-size:11px;text-transform:uppercase;'>
                                <th style='padding:8px 10px;text-align:left;'>Nama Pelari</th>
                                <th style='padding:8px 10px;text-align:left;'>Kategori</th>
                                <th style='padding:8px 10px;text-align:center;'>Jersey</th>
                                <th style='padding:8px 10px;text-align:left;'>Kode Tiket</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$participantsList}
                        </tbody>
                    </table>

                    <!-- Racepack Collection Info -->
                    <div style='margin-top:28px;padding:18px;background:#eff6ff;border-left:4px solid #3b82f6;border-radius:12px;font-size:13px;color:#1e40af;line-height:1.6;'>
                        <strong style='font-size:14px;color:#1e3a8a;'>📦 Informasi Pengambilan Racepack (RPC):</strong><br>
                        Lokasi: <strong>{$event->rpc_location}</strong><br>
                        Jadwal: <strong>" . ($event->rpc_start_date ? $event->rpc_start_date->format('d M Y') : '-') . " s/d " . ($event->rpc_end_date ? $event->rpc_end_date->format('d M Y') : '-') . "</strong><br>
                        <em>Persyaratan: Tunjukkan E-Ticket resmi yang telah dikirim ke masing-masing email peserta beserta kartu identitas asli (KTP/SIM/Paspor).</em>
                    </div>

                    <!-- Call To Action Button -->
                    <div style='margin:32px 0 10px 0;text-align:center;'>
                        <a href='{$orderUrl}' style='display:inline-block;padding:14px 32px;background:#059669;color:#ffffff;text-decoration:none;font-weight:800;font-size:14px;border-radius:12px;box-shadow:0 4px 12px rgba(5,150,105,0.3);'>
                            Lihat E-Ticket &amp; Detail Pesanan &rarr;
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div style='background:#f8fafc;padding:20px 24px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;'>
                    Email ini dikirimkan otomatis oleh sistem pendaftaran resmi <strong>{$event->title}</strong>.
                </div>
            </div>
        </div>";
    }

    /**
     * Build HTML Template for Official Runner E-Ticket
     */
    public function buildTicketHtml(Participant $participant): string
    {
        $participant->loadMissing(['transaction.event', 'ticketCategory', 'jerseySize']);
        $transaction = $participant->transaction;
        $event = $transaction->event;
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($participant->ticket_code);
        $orderUrl = route('order.show', ['invoice' => $transaction->invoice_number]);

        $bibDisplay = $participant->bib_number ?: $participant->bib_name;

        return "
        <div style='background-color:#f1f5f9;padding:30px 10px;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;'>
            <div style='max-width:580px;margin:auto;background:#ffffff;border:2px solid #0284c7;border-radius:22px;overflow:hidden;box-shadow:0 12px 28px -6px rgba(2,132,199,0.15);'>
                <!-- Header Runner Pass -->
                <div style='background:linear-gradient(135deg,#0284c7,#0369a1);color:#ffffff;padding:28px 20px;text-align:center;'>
                    <span style='display:inline-block;padding:4px 14px;background:rgba(255,255,255,0.22);border-radius:20px;font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;'>Pass Masuk Lomba</span>
                    <h1 style='margin:10px 0 2px 0;font-size:24px;font-weight:900;letter-spacing:0.5px;'>OFFICIAL RUNNER E-TICKET</h1>
                    <p style='margin:0;font-size:13px;opacity:0.95;font-weight:500;'>{$event->title}</p>
                </div>

                <!-- Ticket Core Body -->
                <div style='padding:28px 24px;text-align:center;'>
                    <!-- QR Code Showcase Box -->
                    <div style='display:inline-block;padding:14px;background:#ffffff;border:2px solid #bae6fd;border-radius:18px;box-shadow:0 4px 8px rgba(0,0,0,0.04);'>
                        <img src='{$qrUrl}' alt='QR Code E-Ticket' style='width:180px;height:180px;display:block;margin:auto;' />
                    </div>
                    
                    <div style='margin-top:14px;'>
                        <div style='display:inline-block;padding:5px 16px;background:#e0f2fe;color:#0369a1;border-radius:8px;font-weight:900;font-size:18px;font-family:monospace;letter-spacing:2px;'>
                            {$participant->ticket_code}
                        </div>
                        <div style='font-size:12px;color:#64748b;margin-top:6px;'>Tunjukkan QR Code ini kepada panitia saat penukaran Racepack (RPC).</div>
                    </div>

                    <!-- BIB Number Banner -->
                    <div style='margin:20px 0 24px 0;padding:14px;background:#fff7ed;border:2px dashed #fb923c;border-radius:14px;'>
                        <div style='font-size:11px;font-weight:800;color:#c2410c;text-transform:uppercase;letter-spacing:1px;'>NOMOR DADA RESMI (BIB)</div>
                        <div style='font-size:32px;font-weight:900;color:#ea580c;letter-spacing:2px;margin:4px 0;font-family:monospace;'>{$bibDisplay}</div>
                        <div style='font-size:13px;font-weight:700;color:#9a3412;'>{$participant->bib_name}</div>
                    </div>

                    <!-- Runner Details Table -->
                    <div style='text-align:left;border-top:1px dashed #cbd5e1;padding-top:20px;'>
                        <table style='width:100%;font-size:13px;line-height:1.7;color:#334155;'>
                            <tr>
                                <td style='width:38%;color:#64748b;padding:3px 0;'>Nama Pelari</td>
                                <td style='padding:3px 0;'>: <strong style='color:#0f172a;font-size:14px;'>{$participant->full_name}</strong></td>
                            </tr>
                            <tr>
                                <td style='color:#64748b;padding:3px 0;'>Kategori Lomba</td>
                                <td style='padding:3px 0;'>: <strong style='color:#0284c7;'>{$participant->ticketCategory->name}</strong></td>
                            </tr>
                            <tr>
                                <td style='color:#64748b;padding:3px 0;'>Ukuran Jersey</td>
                                <td style='padding:3px 0;'>: <strong style='color:#ea580c;'>{$participant->jerseySize->size_code}</strong></td>
                            </tr>
                            <tr>
                                <td style='color:#64748b;padding:3px 0;'>Golongan Darah</td>
                                <td style='padding:3px 0;'>: <strong>" . ($participant->blood_type ?: '-') . "</strong></td>
                            </tr>
                            <tr>
                                <td style='color:#64748b;padding:3px 0;'>Kontak Darurat</td>
                                <td style='padding:3px 0;'>: " . ($participant->emergency_contact_name ? "{$participant->emergency_contact_name} ({$participant->emergency_contact_phone})" : '-') . "</td>
                            </tr>
                            <tr>
                                <td style='color:#64748b;padding:3px 0;'>Tanggal Race</td>
                                <td style='padding:3px 0;'>: <strong>" . ($event->race_date ? $event->race_date->format('d F Y') : '-') . "</strong></td>
                            </tr>
                            <tr>
                                <td style='color:#64748b;padding:3px 0;'>Waktu Flag-Off</td>
                                <td style='padding:3px 0;'>: <strong>" . ($event->race_start_time ? substr($event->race_start_time, 0, 5) . ' WIB' : '16:00 WIB') . "</strong></td>
                            </tr>
                            <tr>
                                <td style='color:#64748b;padding:3px 0;'>Lokasi Flag-Off</td>
                                <td style='padding:3px 0;'>: {$event->venue_name}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Racepack Collection Info Card -->
                    <div style='margin-top:20px;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;font-size:12px;color:#475569;text-align:left;line-height:1.6;'>
                        <strong style='color:#0f172a;'>📍 Lokasi Pengambilan Racepack:</strong><br>{$event->rpc_location}<br>
                        <strong style='color:#0f172a;'>📅 Periode RPC:</strong> " . ($event->rpc_start_date ? $event->rpc_start_date->format('d M Y') : '-') . " s/d " . ($event->rpc_end_date ? $event->rpc_end_date->format('d M Y') : '-') . "<br>
                        <span style='color:#b45309;'><em>Bawa kartu identitas asli (KTP/SIM/Paspor) saat penukaran.</em></span>
                    </div>

                    <div style='margin-top:24px;'>
                        <a href='{$orderUrl}' style='display:inline-block;padding:12px 28px;background:#0284c7;color:#ffffff;text-decoration:none;font-weight:700;font-size:13px;border-radius:10px;'>
                            Buka E-Ticket Digital di Browser &rarr;
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div style='background:#f8fafc;padding:16px;text-align:center;font-size:11px;color:#64748b;border-top:1px solid #e2e8f0;'>
                    E-Ticket ini adalah dokumen sah peserta <strong>{$event->title}</strong>. Simpan email ini di smartphone Anda.
                </div>
            </div>
        </div>";
    }
}
