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
     * Test connection & sender domain to Mailketing API
     */
    public function testConnection(?string $testRecipient = null): array
    {
        $recipient = $testRecipient ?: $this->senderEmail;

        try {
            $payload = [
                'api_token' => $this->apiToken,
                'from_email' => $this->senderEmail,
                'from_name' => $this->senderName,
                'recipient' => $recipient,
                'recipient_name' => 'Admin Tester',
                'subject' => 'Tes Koneksi Mailketing API - RegRun',
                'content' => '<p>Halo Admin, ini adalah pesan uji koneksi dari sistem registrasi lari RegRun ke Mailketing API. Jika Anda menerima email ini, integrasi email telah berhasil terhubung 100%.</p>',
            ];

            $response = Http::asForm()->timeout(15)->post($this->apiUrl, $payload);
            $resData = $response->json();

            if ($response->successful() && (($resData['status'] ?? '') === 'success' || stripos($resData['response'] ?? '', 'Mail Sent') !== false)) {
                return [
                    'connected' => true,
                    'status' => 'OK',
                    'sender_email' => $this->senderEmail,
                    'sender_name' => $this->senderName,
                    'message' => $resData['response'] ?? 'Email uji coba berhasil dikirim via Mailketing.',
                ];
            }

            return [
                'connected' => false,
                'status' => 'FAILED',
                'error' => $resData['response'] ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'status' => 'EXCEPTION',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 1. Send Pending Payment Notification (UNPAID) upon Registration
     */
    public function sendPendingPaymentEmail(Transaction $transaction): bool
    {
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
    protected function buildPendingPaymentHtml(Transaction $transaction): string
    {
        $event = $transaction->event;
        $orderUrl = route('order.show', ['invoice' => $transaction->invoice_number]);
        $payUrl = $transaction->tripay_checkout_url ?: $orderUrl;

        $itemsHtml = '';
        foreach ($transaction->items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;font-weight:600;color:#1e293b;'>{$item->ticketCategory->name}</td>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:center;color:#475569;'>{$item->quantity}</td>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:600;color:#1e293b;'>Rp " . number_format($item->subtotal, 0, ',', '.') . "</td>
            </tr>";
        }

        $payCodeHtml = '';
        if (!empty($transaction->tripay_pay_code)) {
            $payCodeHtml = "
            <div style='margin:16px 0;padding:16px;background:#fef3c7;border:2px dashed #f59e0b;border-radius:10px;text-align:center;'>
                <div style='font-size:12px;font-weight:bold;color:#92400e;text-transform:uppercase;letter-spacing:0.5px;'>Nomor Pembayaran / Virtual Account ({$transaction->payment_method})</div>
                <div style='font-size:24px;font-weight:900;color:#78350f;letter-spacing:2px;margin:8px 0;font-family:monospace;'>{$transaction->tripay_pay_code}</div>
                <div style='font-size:11px;color:#b45309;'>Silakan transfer sesuai nominal total tagihan sebelum batas waktu berakhir.</div>
            </div>";
        }

        $expiresAtText = $transaction->expires_at ? $transaction->expires_at->format('d M Y, H:i') . ' WIB' : '60 Menit';

        return "
        <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;max-width:620px;margin:auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);'>
            <!-- Header -->
            <div style='background:linear-gradient(135deg,#ea580c,#c2410c);color:#ffffff;padding:28px 24px;text-align:center;'>
                <span style='display:inline-block;padding:4px 12px;background:rgba(255,255,255,0.2);border-radius:20px;font-size:11px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;'>Pendaftaran Berhasil Dibuat</span>
                <h2 style='margin:10px 0 4px 0;font-size:22px;font-weight:800;'>MENUNGGU PEMBAYARAN</h2>
                <p style='margin:0;font-size:14px;opacity:0.95;'>{$event->title}</p>
            </div>

            <!-- Body -->
            <div style='padding:28px 24px;'>
                <p style='font-size:15px;color:#334155;margin-top:0;'>Halo <strong>{$transaction->buyer_name}</strong>,</p>
                <p style='font-size:14px;color:#64748b;line-height:1.6;'>Pendaftaran tiket event lari Anda telah berhasil dicatat. Silakan segera selesaikan pembayaran untuk mengamankan slot kuota tiket dan nomor BIB Anda.</p>

                <!-- Order Meta Card -->
                <table style='width:100%;margin:16px 0;background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;font-size:13px;color:#334155;'>
                    <tr>
                        <td style='padding:4px 0;color:#64748b;'>No. Invoice</td>
                        <td style='padding:4px 0;font-weight:bold;color:#0f172a;'>: {$transaction->invoice_number}</td>
                    </tr>
                    <tr>
                        <td style='padding:4px 0;color:#64748b;'>Metode Pembayaran</td>
                        <td style='padding:4px 0;font-weight:bold;color:#ea580c;'>: {$transaction->payment_method} (Tripay)</td>
                    </tr>
                    <tr>
                        <td style='padding:4px 0;color:#64748b;'>Batas Pembayaran</td>
                        <td style='padding:4px 0;font-weight:bold;color:#dc2626;'>: {$expiresAtText}</td>
                    </tr>
                </table>

                {$payCodeHtml}

                <!-- Items Breakdown -->
                <h4 style='margin:24px 0 10px 0;font-size:14px;font-weight:700;color:#0f172a;'>Rincian Tagihan Pendaftaran:</h4>
                <table style='width:100%;border-collapse:collapse;font-size:13px;'>
                    <thead>
                        <tr style='background:#f1f5f9;color:#475569;text-align:left;'>
                            <th style='padding:10px;border-top-left-radius:8px;'>Kategori</th>
                            <th style='padding:10px;text-align:center;'>Qty</th>
                            <th style='padding:10px;text-align:right;border-top-right-radius:8px;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                        <tr>
                            <td colspan='2' style='padding:10px;color:#64748b;text-align:right;'>Biaya Layanan Gateway:</td>
                            <td style='padding:10px;text-align:right;color:#64748b;'>Rp " . number_format($transaction->fee_amount, 0, ',', '.') . "</td>
                        </tr>
                        <tr style='background:#f8fafc;'>
                            <td colspan='2' style='padding:12px 10px;font-weight:bold;font-size:15px;color:#0f172a;text-align:right;'>Total Tagihan:</td>
                            <td style='padding:12px 10px;font-weight:900;font-size:17px;color:#ea580c;text-align:right;'>Rp " . number_format($transaction->grand_total, 0, ',', '.') . "</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Call to Action Button -->
                <div style='margin:32px 0 24px 0;text-align:center;'>
                    <a href='{$payUrl}' style='display:inline-block;padding:14px 32px;background:#ea580c;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;border-radius:12px;box-shadow:0 4px 12px rgba(234,88,12,0.3);'>
                        Bayar Sekarang via Tripay &rarr;
                    </a>
                    <div style='margin-top:10px;font-size:11px;color:#94a3b8;'>
                        Atau lihat detail di: <a href='{$orderUrl}' style='color:#ea580c;'>{$orderUrl}</a>
                    </div>
                </div>

                <div style='padding:14px;background:#f1f5f9;border-radius:10px;font-size:12px;color:#64748b;line-height:1.5;'>
                    💡 <strong>Catatan:</strong> Pembayaran diverifikasi otomatis oleh sistem Tripay dalam beberapa detik. Setelah lunas, bukti pembayaran dan E-Ticket resmi pelari akan dikirimkan otomatis ke email Anda.
                </div>
            </div>

            <!-- Footer -->
            <div style='background:#f8fafc;padding:16px;text-align:center;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;'>
                Email ini dikirim otomatis oleh sistem pendaftaran resmi {$event->title}.<br>
                Jika Anda tidak merasa mendaftar, abaikan email ini.
            </div>
        </div>";
    }

    /**
     * Build HTML Template for Invoice / Payment Receipt (PAID)
     */
    protected function buildInvoiceHtml(Transaction $transaction): string
    {
        $event = $transaction->event;
        $orderUrl = route('order.show', ['invoice' => $transaction->invoice_number]);

        $itemsHtml = '';
        foreach ($transaction->items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;font-weight:600;color:#1e293b;'>{$item->ticketCategory->name}</td>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:center;color:#475569;'>{$item->quantity}</td>
                <td style='padding:10px;border-bottom:1px solid #f1f5f9;text-align:right;font-weight:600;color:#1e293b;'>Rp " . number_format($item->subtotal, 0, ',', '.') . "</td>
            </tr>";
        }

        $participantsList = '';
        foreach ($transaction->participants as $idx => $p) {
            $num = $idx + 1;
            $bibText = $p->bib_number ? " [BIB: <strong>{$p->bib_number}</strong>]" : '';
            $participantsList .= "<li style='margin-bottom:8px;line-height:1.5;'>
                <strong>Peserta #{$num}:</strong> {$p->full_name} &bull; {$p->ticketCategory->name} &bull; Size: <strong>{$p->jerseySize->size_code}</strong>{$bibText}
            </li>";
        }

        $paidAtText = $transaction->paid_at ? $transaction->paid_at->format('d M Y, H:i') . ' WIB' : now()->format('d M Y, H:i') . ' WIB';

        return "
        <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;max-width:620px;margin:auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);'>
            <!-- Header -->
            <div style='background:linear-gradient(135deg,#059669,#047857);color:#ffffff;padding:28px 24px;text-align:center;'>
                <span style='display:inline-block;padding:4px 12px;background:rgba(255,255,255,0.2);border-radius:20px;font-size:11px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;'>Bukti Transaksi Resmi</span>
                <h2 style='margin:10px 0 4px 0;font-size:22px;font-weight:800;'>PEMBAYARAN LUNAS</h2>
                <p style='margin:0;font-size:14px;opacity:0.95;'>{$event->title}</p>
            </div>

            <!-- Body -->
            <div style='padding:28px 24px;'>
                <p style='font-size:15px;color:#334155;margin-top:0;'>Halo <strong>{$transaction->buyer_name}</strong>,</p>
                <p style='font-size:14px;color:#64748b;line-height:1.6;'>Terima kasih! Pembayaran Anda telah kami terima dan diverifikasi lunas oleh sistem Tripay Payment Gateway.</p>

                <!-- Status Card -->
                <table style='width:100%;margin:16px 0;background:#f0fdf4;padding:14px;border-radius:12px;border:1px solid #bbf7d0;font-size:13px;color:#166534;'>
                    <tr>
                        <td style='padding:4px 0;'>No. Invoice</td>
                        <td style='padding:4px 0;font-weight:bold;'>: {$transaction->invoice_number}</td>
                    </tr>
                    <tr>
                        <td style='padding:4px 0;'>Status</td>
                        <td style='padding:4px 0;font-weight:bold;color:#15803d;'>: LUNAS (PAID) &check;</td>
                    </tr>
                    <tr>
                        <td style='padding:4px 0;'>Metode Bayar</td>
                        <td style='padding:4px 0;font-weight:bold;'>: {$transaction->payment_method}</td>
                    </tr>
                    <tr>
                        <td style='padding:4px 0;'>Waktu Lunas</td>
                        <td style='padding:4px 0;font-weight:bold;'>: {$paidAtText}</td>
                    </tr>
                </table>

                <!-- Items Breakdown -->
                <h4 style='margin:24px 0 10px 0;font-size:14px;font-weight:700;color:#0f172a;'>Rincian Tiket Lomba:</h4>
                <table style='width:100%;border-collapse:collapse;font-size:13px;'>
                    <thead>
                        <tr style='background:#f1f5f9;color:#475569;text-align:left;'>
                            <th style='padding:10px;border-top-left-radius:8px;'>Kategori</th>
                            <th style='padding:10px;text-align:center;'>Qty</th>
                            <th style='padding:10px;text-align:right;border-top-right-radius:8px;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                        <tr>
                            <td colspan='2' style='padding:10px;color:#64748b;text-align:right;'>Biaya Layanan:</td>
                            <td style='padding:10px;text-align:right;color:#64748b;'>Rp " . number_format($transaction->fee_amount, 0, ',', '.') . "</td>
                        </tr>
                        <tr style='background:#f8fafc;'>
                            <td colspan='2' style='padding:12px 10px;font-weight:bold;font-size:15px;color:#0f172a;text-align:right;'>Total Bayar:</td>
                            <td style='padding:12px 10px;font-weight:900;font-size:17px;color:#059669;text-align:right;'>Rp " . number_format($transaction->grand_total, 0, ',', '.') . "</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Registered Runners -->
                <h4 style='margin:24px 0 10px 0;font-size:14px;font-weight:700;color:#0f172a;'>Daftar Pelari Terdaftar:</h4>
                <ul style='font-size:13px;color:#334155;padding-left:20px;margin:0;'>
                    {$participantsList}
                </ul>

                <!-- Racepack Collection Info -->
                <div style='margin-top:28px;padding:16px;background:#eff6ff;border-left:4px solid #3b82f6;border-radius:8px;font-size:13px;color:#1e40af;line-height:1.6;'>
                    <strong>📦 Informasi Pengambilan Racepack (RPC):</strong><br>
                    Lokasi: <strong>{$event->rpc_location}</strong><br>
                    Jadwal: " . ($event->rpc_start_date ? $event->rpc_start_date->format('d M Y') : '-') . " s/d " . ($event->rpc_end_date ? $event->rpc_end_date->format('d M Y') : '-') . "<br>
                    <em>Syarat penukaran: Tunjukkan E-Ticket resmi yang telah dikirim ke masing-masing email peserta beserta kartu identitas asli (KTP/SIM/Paspor).</em>
                </div>

                <div style='margin:28px 0 10px 0;text-align:center;'>
                    <a href='{$orderUrl}' style='display:inline-block;padding:12px 28px;background:#059669;color:#ffffff;text-decoration:none;font-weight:bold;font-size:13px;border-radius:10px;'>
                        Lihat E-Ticket &amp; Detail Pesanan &rarr;
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div style='background:#f8fafc;padding:16px;text-align:center;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;'>
                Email ini dikirim otomatis oleh sistem pendaftaran resmi {$event->title}.
            </div>
        </div>";
    }

    /**
     * Build HTML Template for Official Runner E-Ticket
     */
    protected function buildTicketHtml(Participant $participant): string
    {
        $transaction = $participant->transaction;
        $event = $transaction->event;
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($participant->qr_code_hash);
        $orderUrl = route('order.show', ['invoice' => $transaction->invoice_number]);

        $bibDisplay = $participant->bib_number ? "BIB: {$participant->bib_number}" : "BIB: {$participant->bib_name}";

        return "
        <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;max-width:580px;margin:auto;background:#ffffff;border:2px solid #0284c7;border-radius:18px;overflow:hidden;box-shadow:0 8px 16px -2px rgba(2,132,199,0.1);'>
            <!-- Header -->
            <div style='background:linear-gradient(135deg,#0284c7,#0369a1);color:#ffffff;padding:24px 20px;text-align:center;'>
                <span style='display:inline-block;padding:3px 12px;background:rgba(255,255,255,0.25);border-radius:20px;font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;'>Pass Masuk Lomba</span>
                <h2 style='margin:8px 0 2px 0;font-size:22px;font-weight:900;letter-spacing:0.5px;'>OFFICIAL RUNNER E-TICKET</h2>
                <p style='margin:0;font-size:13px;opacity:0.95;'>{$event->title}</p>
            </div>

            <!-- Ticket Core Body -->
            <div style='padding:24px;text-align:center;'>
                <!-- QR Code Box -->
                <div style='display:inline-block;padding:12px;background:#ffffff;border:2px solid #e0f2fe;border-radius:14px;box-shadow:0 2px 4px rgba(0,0,0,0.04);'>
                    <img src='{$qrUrl}' alt='QR Code E-Ticket' style='width:180px;height:180px;display:block;' />
                </div>
                
                <div style='margin-top:14px;'>
                    <span style='display:inline-block;padding:4px 14px;background:#e0f2fe;color:#0369a1;border-radius:8px;font-weight:800;font-size:16px;font-family:monospace;letter-spacing:2px;'>
                        {$participant->ticket_code}
                    </span>
                    <div style='font-size:12px;color:#64748b;margin-top:4px;'>Tunjukkan QR Code ini kepada panitia saat penukaran Racepack.</div>
                </div>

                <!-- Runner Details Table -->
                <div style='text-align:left;margin-top:24px;border-top:1px dashed #cbd5e1;padding-top:20px;'>
                    <table style='width:100%;font-size:13px;line-height:1.7;color:#334155;'>
                        <tr>
                            <td style='width:38%;color:#64748b;'>Nama Pelari</td>
                            <td>: <strong style='color:#0f172a;font-size:14px;'>{$participant->full_name}</strong></td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Nama di BIB</td>
                            <td>: <strong style='color:#0284c7;'>{$participant->bib_name}</strong></td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Nomor BIB Resmi</td>
                            <td>: <strong style='font-size:15px;color:#ea580c;font-family:monospace;'>{$bibDisplay}</strong></td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Kategori Lomba</td>
                            <td>: <strong>{$participant->ticketCategory->name}</strong></td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Ukuran Jersey</td>
                            <td>: <strong>{$participant->jerseySize->size_code}</strong></td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Golongan Darah</td>
                            <td>: {$participant->blood_type}</td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Kontak Darurat</td>
                            <td>: {$participant->emergency_contact_name} ({$participant->emergency_contact_phone})</td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Tanggal Race</td>
                            <td>: <strong>" . ($event->race_date ? $event->race_date->format('d F Y') : '-') . "</strong></td>
                        </tr>
                        <tr>
                            <td style='color:#64748b;'>Lokasi Flag-Off</td>
                            <td>: {$event->venue_name}</td>
                        </tr>
                    </table>
                </div>

                <!-- Racepack Details -->
                <div style='margin-top:20px;padding:12px;background:#f8fafc;border-radius:10px;font-size:12px;color:#475569;text-align:left;'>
                    <strong>📍 Lokasi Pengambilan Racepack:</strong> {$event->rpc_location}<br>
                    <strong>📅 Periode RPC:</strong> " . ($event->rpc_start_date ? $event->rpc_start_date->format('d M Y') : '-') . " s/d " . ($event->rpc_end_date ? $event->rpc_end_date->format('d M Y') : '-') . "
                </div>

                <div style='margin-top:20px;'>
                    <a href='{$orderUrl}' style='font-size:12px;font-weight:bold;color:#0284c7;text-decoration:none;'>
                        Buka Halaman E-Ticket Digital &rarr;
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div style='background:#f1f5f9;padding:12px;text-align:center;font-size:11px;color:#64748b;border-top:1px solid #e2e8f0;'>
                E-Ticket ini adalah dokumen sah peserta {$event->title}. Simpan di smartphone Anda.
            </div>
        </div>";
    }
}
