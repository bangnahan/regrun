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
    protected string $apiUrl = 'https://api.mailketing.co.id/api/v2/send';
    protected string $fallbackApiUrl = 'https://api.mailketing.co.id/api/v1/send';

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
                    $htmlContent = $this->buildFallbackSampleHtml('Tagihan Menunggu Pembayaran');
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
                    $htmlContent = $this->buildFallbackSampleHtml('Official E-Ticket Pelari');
                }
                break;

            case 'simple':
            case 'connection':
                $typeName = 'Tes Koneksi Sederhana';
                $subject = 'Tes Koneksi Mailketing API - RegRun';
                $htmlContent = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Tes Koneksi</title></head><body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;"><div style="max-width:520px;margin:auto;padding:24px;border:1px solid #e2e8f0;border-radius:16px;background:#ffffff;"><h2 style="color:#0f172a;margin:0 0 10px 0;">Koneksi Mailketing API Aktif</h2><p style="color:#475569;font-size:14px;line-height:1.6;">Halo Admin, sistem registrasi lari RegRun berhasil terhubung dengan akun Mailketing Anda. Pengiriman email transaksional dan E-Ticket dengan QR Code siap berjalan.</p><div style="padding:12px;background:#f1f5f9;border-radius:8px;font-size:12px;color:#64748b;">Sender: <strong>' . htmlspecialchars($this->senderEmail) . '</strong> &bull; API Token: Terverifikasi</div></div></body></html>';
                break;

            case 'invoice':
            default:
                $typeName = 'Bukti Pembayaran Lunas (Invoice)';
                if ($transaction) {
                    $subject = "[CONTOH] Bukti Pembayaran Lunas {$transaction->invoice_number} - " . ($transaction->event->title ?? 'Nusantara Sunset Run 2026');
                    $htmlContent = $this->buildInvoiceHtml($transaction);
                } else {
                    $subject = "[CONTOH] Bukti Pembayaran Lunas INV-SAMPLE-01 - Nusantara Sunset Run 2026";
                    $htmlContent = $this->buildFallbackSampleHtml('Bukti Pembayaran Lunas');
                }
                break;
        }

        $sent = $this->sendMail(
            $recipientEmail,
            'Tester RegRun',
            $subject,
            $htmlContent,
            'sample_' . $type,
            $transaction?->id,
            null
        );

        return [
            'connected' => $sent,
            'status' => $sent ? 'OK' : 'FAILED',
            'type_name' => $typeName,
            'message' => $sent
                ? "Email contoh '{$typeName}' berhasil dikirim ke {$recipientEmail} via Mailketing API."
                : "Gagal mengirim email contoh '{$typeName}' ke {$recipientEmail}.",
        ];
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
     * Core dispatch to Mailketing API v2 (with fallback to v1)
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

        $payload = [
            'api_token' => $this->apiToken,
            'from_email' => $this->senderEmail,
            'from_name' => $this->senderName,
            'recipient' => $recipientEmail,
            'recipient_name' => $recipientName ?: $recipientEmail,
            'subject' => $subject,
            'content' => $htmlBody,
        ];

        try {
            // Priority 1: Mailketing API v2 (application/json)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(20)->post($this->apiUrl, $payload);

            $resData = $response->json();

            if ($response->successful() && (!empty($resData['success']) || ($resData['status'] ?? '') === 'success' || !empty($resData['data']['message_id']))) {
                $status = 'sent';
                $messageId = $resData['data']['message_id'] ?? ($resData['message_id'] ?? ('MK-' . uniqid()));
                Log::info("Mailketing v2 email successfully sent to {$recipientEmail} [{$type}]");
            } else {
                // Priority 2: Fallback to Mailketing v1 (asForm)
                Log::warning("Mailketing v2 failed, attempting v1 fallback for {$recipientEmail}: " . ($resData['message'] ?? $response->body()));
                $v1Response = Http::asForm()->timeout(20)->post($this->fallbackApiUrl, $payload);
                $v1Data = $v1Response->json();

                if ($v1Response->successful() && (($v1Data['status'] ?? '') === 'success' || stripos($v1Data['response'] ?? '', 'Mail Sent') !== false)) {
                    $status = 'sent';
                    $messageId = $v1Data['message_id'] ?? ('MK-V1-' . uniqid());
                    Log::info("Mailketing v1 fallback successfully sent to {$recipientEmail} [{$type}]");
                } else {
                    $errorMessage = $v1Data['response'] ?? ($resData['message'] ?? $v1Response->body());
                    Log::error("Mailketing both v2 and v1 failed for {$recipientEmail}: {$errorMessage}");
                }
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
                <td style=\"padding:12px;border-bottom:1px solid #f1f5f9;font-weight:bold;color:#1e293b;font-size:13px;\">{$item->ticketCategory->name}</td>
                <td style=\"padding:12px;border-bottom:1px solid #f1f5f9;text-align:center;color:#64748b;font-size:13px;\">{$item->quantity}x</td>
                <td style=\"padding:12px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:bold;color:#1e293b;font-size:13px;\">Rp " . number_format($item->subtotal, 0, ',', '.') . "</td>
            </tr>";
        }

        $participantsHtml = '';
        foreach ($transaction->participants as $idx => $p) {
            $num = $idx + 1;
            $participantsHtml .= "<tr style=\"font-size:12px;color:#334155;\">
                <td style=\"padding:8px 10px;border-bottom:1px solid #f1f5f9;\">#{$num} <strong>{$p->full_name}</strong></td>
                <td style=\"padding:8px 10px;border-bottom:1px solid #f1f5f9;\">{$p->ticketCategory->name}</td>
                <td style=\"padding:8px 10px;border-bottom:1px solid #f1f5f9;text-align:center;font-weight:bold;color:#ea580c;\">{$p->jerseySize->size_code}</td>
            </tr>";
        }

        $payCodeHtml = '';
        if (!empty($transaction->tripay_pay_code)) {
            $payCodeHtml = "
            <div style=\"margin:20px 0;padding:18px;background-color:#fffbeb;border:2px dashed #f59e0b;border-radius:14px;text-align:center;\">
                <div style=\"font-size:11px;font-weight:bold;color:#b45309;text-transform:uppercase;letter-spacing:1px;\">KODE PEMBAYARAN / VIRTUAL ACCOUNT ({$transaction->payment_method})</div>
                <div style=\"font-size:26px;font-weight:bold;color:#78350f;letter-spacing:3px;margin:10px 0;font-family:Courier,monospace;\">{$transaction->tripay_pay_code}</div>
                <div style=\"font-size:12px;color:#92400e;\">Silakan transfer sesuai nominal total tagihan tepat sampai 3 digit terakhir.</div>
            </div>";
        }

        $expiresAtText = $transaction->expires_at ? $transaction->expires_at->format('d M Y, H:i') . ' WIB' : '60 Menit';

        return "<!DOCTYPE html>
<html lang=\"id\">
<head>
    <meta charset=\"UTF-8\">
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Tagihan Pendaftaran {$transaction->invoice_number}</title>
</head>
<body style=\"margin:0;padding:24px 10px;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;\">
    <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
            <td align=\"center\">
                <table width=\"600\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width:600px;width:100%;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.06);\">
                    <!-- Header Banner -->
                    <tr>
                        <td align=\"center\" style=\"background:linear-gradient(135deg,#ea580c,#c2410c);padding:32px 24px;color:#ffffff;\">
                            <div style=\"display:inline-block;padding:4px 14px;background-color:rgba(255,255,255,0.22);border-radius:20px;font-size:11px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;\">Menunggu Pembayaran</div>
                            <h1 style=\"margin:12px 0 4px 0;font-size:24px;font-weight:bold;letter-spacing:-0.5px;\">SELESAIKAN PEMBAYARAN</h1>
                            <p style=\"margin:0;font-size:14px;opacity:0.95;\">{$event->title}</p>
                        </td>
                    </tr>
                    <!-- Main Body -->
                    <tr>
                        <td style=\"padding:28px 24px;\">
                            <p style=\"font-size:15px;color:#1e293b;margin-top:0;line-height:1.6;\">
                                Halo <strong>{$transaction->buyer_name}</strong>,<br>
                                Pendaftaran tiket lomba Anda telah berhasil dibuat. Segera lakukan pembayaran sebelum batas waktu berakhir agar slot kuota tiket dan nomor BIB Anda tidak dibatalkan.
                            </p>

                            <!-- Meta Information Box -->
                            <table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\" style=\"margin:18px 0;background-color:#f8fafc;padding:12px;border-radius:12px;border:1px solid #e2e8f0;font-size:13px;\">
                                <tr>
                                    <td style=\"color:#64748b;width:40%;\">Nomor Invoice</td>
                                    <td style=\"font-weight:bold;color:#0f172a;font-family:Courier,monospace;\">: {$transaction->invoice_number}</td>
                                </tr>
                                <tr>
                                    <td style=\"color:#64748b;\">Metode Pembayaran</td>
                                    <td style=\"font-weight:bold;color:#ea580c;\">: {$transaction->payment_method} (Tripay)</td>
                                </tr>
                                <tr>
                                    <td style=\"color:#64748b;\">Batas Pembayaran</td>
                                    <td style=\"font-weight:bold;color:#dc2626;\">: {$expiresAtText}</td>
                                </tr>
                            </table>

                            {$payCodeHtml}

                            <!-- Cost Breakdown -->
                            <h3 style=\"margin:24px 0 10px 0;font-size:14px;font-weight:bold;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;\">Rincian Tagihan Pendaftaran</h3>
                            <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;\">
                                <thead>
                                    <tr style=\"background-color:#f8fafc;color:#475569;border-bottom:1px solid #e2e8f0;\">
                                        <th style=\"padding:12px;text-align:left;font-size:12px;\">Kategori</th>
                                        <th style=\"padding:12px;text-align:center;font-size:12px;\">Qty</th>
                                        <th style=\"padding:12px;text-align:right;font-size:12px;\">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$itemsHtml}
                                    <tr>
                                        <td colspan=\"2\" style=\"padding:10px 12px;color:#64748b;text-align:right;font-size:12px;\">Biaya Layanan Gateway:</td>
                                        <td style=\"padding:10px 12px;text-align:right;color:#64748b;font-size:12px;\">Rp " . number_format($transaction->fee_amount, 0, ',', '.') . "</td>
                                    </tr>
                                    <tr style=\"background-color:#fff7ed;border-top:2px solid #fed7aa;\">
                                        <td colspan=\"2\" style=\"padding:14px 12px;font-weight:bold;font-size:15px;color:#0f172a;text-align:right;\">TOTAL PEMBAYARAN:</td>
                                        <td style=\"padding:14px 12px;font-weight:bold;font-size:18px;color:#ea580c;text-align:right;\">Rp " . number_format($transaction->grand_total, 0, ',', '.') . "</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Runners Summary -->
                            <h3 style=\"margin:24px 0 10px 0;font-size:14px;font-weight:bold;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;\">Daftar Pelari yang Didaftarkan</h3>
                            <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;\">
                                <thead>
                                    <tr style=\"background-color:#f1f5f9;color:#475569;font-size:11px;text-transform:uppercase;\">
                                        <th style=\"padding:8px 10px;text-align:left;\">Nama Pelari</th>
                                        <th style=\"padding:8px 10px;text-align:left;\">Kategori</th>
                                        <th style=\"padding:8px 10px;text-align:center;\">Jersey</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$participantsHtml}
                                </tbody>
                            </table>

                            <!-- Call to Action Button -->
                            <div style=\"margin:32px 0 20px 0;text-align:center;\">
                                <a href=\"{$payUrl}\" style=\"display:inline-block;padding:16px 36px;background-color:#ea580c;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;border-radius:12px;box-shadow:0 6px 16px rgba(234,88,12,0.3);\">
                                    Bayar Sekarang via Tripay &rarr;
                                </a>
                                <div style=\"margin-top:12px;font-size:12px;color:#94a3b8;\">
                                    Atau lihat invoice di: <a href=\"{$orderUrl}\" style=\"color:#ea580c;text-decoration:none;font-weight:bold;\">{$orderUrl}</a>
                                </div>
                            </div>

                            <!-- Note -->
                            <div style=\"padding:14px;background-color:#f0fdf4;border-left:4px solid #16a34a;border-radius:8px;font-size:12px;color:#15803d;line-height:1.6;\">
                                <strong>&#10003; Verifikasi Otomatis:</strong> Pembayaran diverifikasi otomatis dalam beberapa detik. Setelah lunas, bukti transaksi dan E-Ticket resmi akan dikirim otomatis ke email Anda.
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align=\"center\" style=\"background-color:#f8fafc;padding:18px 24px;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;\">
                            Email ini dikirim otomatis oleh sistem pendaftaran resmi <strong>{$event->title}</strong>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
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
                <td style=\"padding:12px;border-bottom:1px solid #f1f5f9;font-weight:bold;color:#1e293b;font-size:13px;\">{$item->ticketCategory->name}</td>
                <td style=\"padding:12px;border-bottom:1px solid #f1f5f9;text-align:center;color:#64748b;font-size:13px;\">{$item->quantity}x</td>
                <td style=\"padding:12px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:bold;color:#1e293b;font-size:13px;\">Rp " . number_format($item->subtotal, 0, ',', '.') . "</td>
            </tr>";
        }

        $participantsList = '';
        foreach ($transaction->participants as $idx => $p) {
            $num = $idx + 1;
            $bibText = $p->bib_number ? " &bull; Nomor BIB: <strong style=\"color:#ea580c;font-family:Courier,monospace;\">{$p->bib_number}</strong>" : '';
            $participantsList .= "<tr style=\"font-size:12px;color:#334155;\">
                <td style=\"padding:10px;border-bottom:1px solid #f1f5f9;\">
                    <strong style=\"color:#0f172a;font-size:13px;\">#{$num} {$p->full_name}</strong><br>
                    <span style=\"color:#64748b;font-size:11px;\">Nama BIB: {$p->bib_name}{$bibText}</span>
                </td>
                <td style=\"padding:10px;border-bottom:1px solid #f1f5f9;font-weight:bold;\">{$p->ticketCategory->name}</td>
                <td style=\"padding:10px;border-bottom:1px solid #f1f5f9;text-align:center;font-weight:bold;color:#059669;\">{$p->jerseySize->size_code}</td>
                <td style=\"padding:10px;border-bottom:1px solid #f1f5f9;font-family:Courier,monospace;font-size:11px;color:#0284c7;font-weight:bold;\">{$p->ticket_code}</td>
            </tr>";
        }

        $paidAtText = $transaction->paid_at ? $transaction->paid_at->format('d M Y, H:i') . ' WIB' : now()->format('d M Y, H:i') . ' WIB';

        return "<!DOCTYPE html>
<html lang=\"id\">
<head>
    <meta charset=\"UTF-8\">
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Bukti Pembayaran Lunas {$transaction->invoice_number}</title>
</head>
<body style=\"margin:0;padding:24px 10px;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;\">
    <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
            <td align=\"center\">
                <table width=\"600\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width:600px;width:100%;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.06);\">
                    <!-- Header -->
                    <tr>
                        <td align=\"center\" style=\"background:linear-gradient(135deg,#059669,#047857);padding:32px 24px;color:#ffffff;\">
                            <div style=\"display:inline-block;padding:4px 14px;background-color:rgba(255,255,255,0.22);border-radius:20px;font-size:11px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;\">Bukti Transaksi Resmi</div>
                            <h1 style=\"margin:12px 0 4px 0;font-size:24px;font-weight:bold;letter-spacing:-0.5px;\">PEMBAYARAN LUNAS</h1>
                            <p style=\"margin:0;font-size:14px;opacity:0.95;\">{$event->title}</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style=\"padding:28px 24px;\">
                            <p style=\"font-size:15px;color:#1e293b;margin-top:0;line-height:1.6;\">
                                Halo <strong>{$transaction->buyer_name}</strong>,<br>
                                Terima kasih! Pembayaran pendaftaran Anda telah berhasil kami terima dan diverifikasi lunas oleh sistem Tripay Payment Gateway.
                            </p>

                            <!-- Status Card -->
                            <table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\" style=\"margin:18px 0;background-color:#f0fdf4;padding:12px;border-radius:12px;border:1px solid #bbf7d0;font-size:13px;\">
                                <tr>
                                    <td style=\"color:#166534;width:40%;\">Nomor Invoice</td>
                                    <td style=\"font-weight:bold;color:#0f172a;font-family:Courier,monospace;\">: {$transaction->invoice_number}</td>
                                </tr>
                                <tr>
                                    <td style=\"color:#166534;\">Status Pembayaran</td>
                                    <td style=\"font-weight:bold;color:#15803d;\">: LUNAS (PAID) &#10003;</td>
                                </tr>
                                <tr>
                                    <td style=\"color:#166534;\">Metode Pembayaran</td>
                                    <td style=\"font-weight:bold;color:#0f172a;\">: {$transaction->payment_method}</td>
                                </tr>
                                <tr>
                                    <td style=\"color:#166534;\">Waktu Pembayaran</td>
                                    <td style=\"font-weight:bold;color:#475569;\">: {$paidAtText}</td>
                                </tr>
                            </table>

                            <!-- Items Breakdown -->
                            <h3 style=\"margin:24px 0 10px 0;font-size:14px;font-weight:bold;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;\">Rincian Pembayaran</h3>
                            <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;\">
                                <thead>
                                    <tr style=\"background-color:#f8fafc;color:#475569;border-bottom:1px solid #e2e8f0;\">
                                        <th style=\"padding:12px;text-align:left;font-size:12px;\">Kategori Lomba</th>
                                        <th style=\"padding:12px;text-align:center;font-size:12px;\">Qty</th>
                                        <th style=\"padding:12px;text-align:right;font-size:12px;\">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$itemsHtml}
                                    <tr>
                                        <td colspan=\"2\" style=\"padding:10px 12px;color:#64748b;text-align:right;font-size:12px;\">Biaya Layanan Gateway:</td>
                                        <td style=\"padding:10px 12px;text-align:right;color:#64748b;font-size:12px;\">Rp " . number_format($transaction->fee_amount, 0, ',', '.') . "</td>
                                    </tr>
                                    <tr style=\"background-color:#f0fdf4;border-top:2px solid #bbf7d0;\">
                                        <td colspan=\"2\" style=\"padding:14px 12px;font-weight:bold;font-size:15px;color:#0f172a;text-align:right;\">TOTAL LUNAS:</td>
                                        <td style=\"padding:14px 12px;font-weight:bold;font-size:18px;color:#059669;text-align:right;\">Rp " . number_format($transaction->grand_total, 0, ',', '.') . "</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Registered Runners -->
                            <h3 style=\"margin:24px 0 10px 0;font-size:14px;font-weight:bold;color:#0f172a;text-transform:uppercase;letter-spacing:0.5px;\">Daftar Pelari Terdaftar</h3>
                            <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;\">
                                <thead>
                                    <tr style=\"background-color:#f1f5f9;color:#475569;font-size:11px;text-transform:uppercase;\">
                                        <th style=\"padding:8px 10px;text-align:left;\">Nama Pelari</th>
                                        <th style=\"padding:8px 10px;text-align:left;\">Kategori</th>
                                        <th style=\"padding:8px 10px;text-align:center;\">Jersey</th>
                                        <th style=\"padding:8px 10px;text-align:left;\">Kode Tiket</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$participantsList}
                                </tbody>
                            </table>

                            <!-- RPC Info -->
                            <div style=\"margin-top:24px;padding:16px;background-color:#eff6ff;border-left:4px solid #3b82f6;border-radius:10px;font-size:13px;color:#1e40af;line-height:1.6;\">
                                <strong style=\"font-size:14px;color:#1e3a8a;\">&#128230; Informasi Pengambilan Racepack (RPC):</strong><br>
                                Lokasi: <strong>{$event->rpc_location}</strong><br>
                                Jadwal: <strong>" . ($event->rpc_start_date ? $event->rpc_start_date->format('d M Y') : '-') . " s/d " . ($event->rpc_end_date ? $event->rpc_end_date->format('d M Y') : '-') . "</strong><br>
                                <em>Persyaratan: Tunjukkan E-Ticket resmi yang telah dikirim ke masing-masing email peserta beserta kartu identitas asli (KTP/SIM/Paspor).</em>
                            </div>

                            <!-- Button -->
                            <div style=\"margin:32px 0 10px 0;text-align:center;\">
                                <a href=\"{$orderUrl}\" style=\"display:inline-block;padding:14px 32px;background-color:#059669;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;border-radius:12px;box-shadow:0 4px 12px rgba(5,150,105,0.3);\">
                                    Lihat E-Ticket &amp; Detail Pesanan &rarr;
                                </a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align=\"center\" style=\"background-color:#f8fafc;padding:18px 24px;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;\">
                            Email ini dikirim otomatis oleh sistem pendaftaran resmi <strong>{$event->title}</strong>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }

    /**
     * Build HTML Template for Official Runner E-Ticket
     */
    public function buildTicketHtml(Participant $participant): string
    {
        $participant->loadMissing(['transaction.event', 'ticketCategory', 'jerseySize']);
        $transaction = $participant->transaction;
        $event = $transaction->event;
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&amp;data=" . urlencode($participant->ticket_code);
        $orderUrl = route('order.show', ['invoice' => $transaction->invoice_number]);

        $bibDisplay = $participant->bib_number ?: $participant->bib_name;

        return "<!DOCTYPE html>
<html lang=\"id\">
<head>
    <meta charset=\"UTF-8\">
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Official E-Ticket {$participant->ticket_code} - {$participant->full_name}</title>
</head>
<body style=\"margin:0;padding:24px 10px;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;\">
    <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
            <td align=\"center\">
                <table width=\"580\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width:580px;width:100%;background-color:#ffffff;border:2px solid #0284c7;border-radius:20px;overflow:hidden;box-shadow:0 10px 26px rgba(2,132,199,0.15);\">
                    <!-- Header -->
                    <tr>
                        <td align=\"center\" style=\"background:linear-gradient(135deg,#0284c7,#0369a1);padding:28px 20px;color:#ffffff;\">
                            <div style=\"display:inline-block;padding:4px 14px;background-color:rgba(255,255,255,0.25);border-radius:20px;font-size:11px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;\">Pass Masuk Lomba</div>
                            <h1 style=\"margin:10px 0 2px 0;font-size:24px;font-weight:bold;letter-spacing:0.5px;\">OFFICIAL RUNNER E-TICKET</h1>
                            <p style=\"margin:0;font-size:13px;opacity:0.95;\">{$event->title}</p>
                        </td>
                    </tr>
                    <!-- Core Pass Body -->
                    <tr>
                        <td align=\"center\" style=\"padding:28px 24px;\">
                            <!-- QR Code Showcase Box -->
                            <div style=\"display:inline-block;padding:12px;background-color:#ffffff;border:2px solid #bae6fd;border-radius:16px;box-shadow:0 4px 8px rgba(0,0,0,0.04);\">
                                <img src=\"{$qrUrl}\" alt=\"QR Code E-Ticket\" width=\"180\" height=\"180\" style=\"width:180px;height:180px;display:block;margin:auto;\" />
                            </div>
                            
                            <div style=\"margin-top:14px;\">
                                <div style=\"display:inline-block;padding:5px 16px;background-color:#e0f2fe;color:#0369a1;border-radius:8px;font-weight:bold;font-size:18px;font-family:Courier,monospace;letter-spacing:2px;\">
                                    {$participant->ticket_code}
                                </div>
                                <div style=\"font-size:12px;color:#64748b;margin-top:6px;\">Tunjukkan QR Code ini kepada panitia saat penukaran Racepack (RPC).</div>
                            </div>

                            <!-- BIB Number Banner -->
                            <div style=\"margin:20px 0;padding:14px;background-color:#fff7ed;border:2px dashed #fb923c;border-radius:14px;\">
                                <div style=\"font-size:11px;font-weight:bold;color:#c2410c;text-transform:uppercase;letter-spacing:1px;\">NOMOR DADA RESMI (BIB)</div>
                                <div style=\"font-size:32px;font-weight:bold;color:#ea580c;letter-spacing:2px;margin:4px 0;font-family:Courier,monospace;\">{$bibDisplay}</div>
                                <div style=\"font-size:13px;font-weight:bold;color:#9a3412;\">{$participant->bib_name}</div>
                            </div>

                            <!-- Runner Profile Details -->
                            <div style=\"text-align:left;border-top:1px dashed #cbd5e1;padding-top:18px;\">
                                <table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"0\" style=\"font-size:13px;color:#334155;line-height:1.6;\">
                                    <tr>
                                        <td style=\"width:38%;color:#64748b;\">Nama Pelari</td>
                                        <td>: <strong style=\"color:#0f172a;font-size:14px;\">{$participant->full_name}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style=\"color:#64748b;\">Kategori Lomba</td>
                                        <td>: <strong style=\"color:#0284c7;\">{$participant->ticketCategory->name}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style=\"color:#64748b;\">Ukuran Jersey</td>
                                        <td>: <strong style=\"color:#ea580c;\">{$participant->jerseySize->size_code}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style=\"color:#64748b;\">Golongan Darah</td>
                                        <td>: <strong>" . ($participant->blood_type ?: '-') . "</strong></td>
                                    </tr>
                                    <tr>
                                        <td style=\"color:#64748b;\">Kontak Darurat</td>
                                        <td>: " . ($participant->emergency_contact_name ? "{$participant->emergency_contact_name} ({$participant->emergency_contact_phone})" : '-') . "</td>
                                    </tr>
                                    <tr>
                                        <td style=\"color:#64748b;\">Tanggal Race</td>
                                        <td>: <strong>" . ($event->race_date ? $event->race_date->format('d F Y') : '-') . "</strong></td>
                                    </tr>
                                    <tr>
                                        <td style=\"color:#64748b;\">Waktu Flag-Off</td>
                                        <td>: <strong>" . ($event->race_start_time ? substr($event->race_start_time, 0, 5) . ' WIB' : '16:00 WIB') . "</strong></td>
                                    </tr>
                                    <tr>
                                        <td style=\"color:#64748b;\">Lokasi Flag-Off</td>
                                        <td>: {$event->venue_name}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- RPC Details -->
                            <div style=\"margin-top:20px;padding:14px;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;font-size:12px;color:#475569;text-align:left;line-height:1.6;\">
                                <strong style=\"color:#0f172a;\">&#128205; Lokasi Pengambilan Racepack:</strong><br>{$event->rpc_location}<br>
                                <strong style=\"color:#0f172a;\">&#128197; Periode RPC:</strong> " . ($event->rpc_start_date ? $event->rpc_start_date->format('d M Y') : '-') . " s/d " . ($event->rpc_end_date ? $event->rpc_end_date->format('d M Y') : '-') . "<br>
                                <span style=\"color:#b45309;\"><em>Wajib membawa kartu identitas asli (KTP/SIM/Paspor) saat penukaran.</em></span>
                            </div>

                            <div style=\"margin-top:24px;\">
                                <a href=\"{$orderUrl}\" style=\"display:inline-block;padding:12px 28px;background-color:#0284c7;color:#ffffff;text-decoration:none;font-weight:bold;font-size:13px;border-radius:10px;\">
                                    Buka E-Ticket Digital di Browser &rarr;
                                </a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align=\"center\" style=\"background-color:#f8fafc;padding:16px;text-align:center;font-size:11px;color:#64748b;border-top:1px solid #e2e8f0;\">
                            E-Ticket ini adalah dokumen sah peserta <strong>{$event->title}</strong>. Simpan email ini di smartphone Anda.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }

    /**
     * Fallback dummy HTML
     */
    protected function buildFallbackSampleHtml(string $title): string
    {
        return "<!DOCTYPE html>
<html lang=\"id\">
<head>
    <meta charset=\"UTF-8\">
    <title>{$title}</title>
</head>
<body style=\"margin:0;padding:24px;background-color:#f8fafc;font-family:Arial,sans-serif;\">
    <div style=\"max-width:560px;margin:auto;background:#ffffff;padding:24px;border:1px solid #e2e8f0;border-radius:14px;\">
        <h2 style=\"color:#ea580c;margin:0 0 12px 0;\">{$title} - Nusantara Sunset Run 2026</h2>
        <p style=\"color:#334155;font-size:14px;line-height:1.6;\">Halo Runner, ini adalah email simulasi resmi sistem registrasi event lari RegRun.</p>
    </div>
</body>
</html>";
    }
}
