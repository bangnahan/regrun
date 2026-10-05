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

    public function __construct()
    {
        $this->apiToken = SystemSetting::get('mailketing_api_token', config('services.mailketing.api_token', env('MAILKETING_API_TOKEN', '308b31d3313311776744479fa8fd7eb3')));
        $this->senderEmail = SystemSetting::get('mailketing_sender_email', config('services.mailketing.sender_email', env('MAILKETING_SENDER_EMAIL', 'hi@jelatik.com')));
        $this->senderName = SystemSetting::get('mailketing_sender_name', config('services.mailketing.sender_name', env('MAILKETING_SENDER_NAME', 'Panitia Event Lari')));
    }

    /**
     * Send Invoice / Payment Confirmation Email to Buyer
     */
    public function sendInvoiceEmail(Transaction $transaction): bool
    {
        $eventTitle = $transaction->event->title ?? 'Event Lari';
        $subject = "Bukti Pendaftaran & Pembayaran Lunas - {$transaction->invoice_number} ({$eventTitle})";

        $htmlContent = $this->buildInvoiceHtml($transaction);

        return $this->sendMail(
            $transaction->buyer_email,
            $subject,
            $htmlContent,
            'invoice',
            $transaction->id,
            null
        );
    }

    /**
     * Send E-Ticket with QR Code to each Participant
     */
    public function sendTicketEmail(Participant $participant): bool
    {
        $transaction = $participant->transaction;
        $event = $transaction->event ?? null;
        $eventTitle = $event->title ?? 'Event Lari';
        $subject = "E-Ticket Resmi: {$participant->full_name} [{$participant->ticket_code}] - {$eventTitle}";

        $htmlContent = $this->buildTicketHtml($participant);

        return $this->sendMail(
            $participant->email,
            $subject,
            $htmlContent,
            'eticket',
            $transaction->id,
            $participant->id
        );
    }

    /**
     * Core dispatch to Mailketing API
     */
    protected function sendMail(
        string $recipientEmail,
        string $subject,
        string $htmlBody,
        string $type,
        ?int $transactionId = null,
        ?int $participantId = null
    ): bool {
        $status = 'failed';
        $errorMessage = null;
        $messageId = null;

        try {
            $payload = [
                'recipient' => $recipientEmail,
                'from_email' => $this->senderEmail,
                'from_name' => $this->senderName,
                'subject' => $subject,
                'content' => $htmlBody,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->timeout(8)->post('https://api.mailketing.co.id/api/v1/send', $payload);

            $resData = $response->json();

            if ($response->successful() && ($resData['status'] ?? '') === 'success') {
                $status = 'sent';
                $messageId = $resData['message_id'] ?? null;
            } else {
                $errorMessage = $resData['response'] ?? $response->body();
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::warning("Mailketing dispatch failed: {$e->getMessage()}");
        }

        // Record into email_logs for auditing in admin panel
        EmailLog::create([
            'transaction_id' => $transactionId,
            'participant_id' => $participantId,
            'recipient_email' => $recipientEmail,
            'email_type' => $type,
            'mailketing_message_id' => $messageId,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);

        return $status === 'sent';
    }

    protected function buildInvoiceHtml(Transaction $transaction): string
    {
        $event = $transaction->event;
        $itemsHtml = '';
        foreach ($transaction->items as $item) {
            $itemsHtml .= "<tr>
                <td style='padding:8px;border-bottom:1px solid #eee;'>{$item->ticketCategory->name}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:center;'>{$item->quantity}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>Rp " . number_format($item->subtotal, 0, ',', '.') . "</td>
            </tr>";
        }

        $participantsList = '';
        foreach ($transaction->participants as $idx => $p) {
            $num = $idx + 1;
            $participantsList .= "<li style='margin-bottom:6px;'>
                <strong>Peserta #{$num}:</strong> {$p->full_name} ({$p->ticketCategory->name}) - Size: <strong>{$p->jerseySize->size_code}</strong> - BIB: {$p->bib_name}
            </li>";
        }

        return "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;'>
            <div style='background:#1e293b;color:#fff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>PEMBAYARAN LUNAS</h2>
                <p style='margin:4px 0 0 0;font-size:14px;color:#94a3b8;'>{$event->title}</p>
            </div>
            <div style='padding:24px;'>
                <p>Halo <strong>{$transaction->buyer_name}</strong>,</p>
                <p>Terima kasih! Pembayaran untuk registrasi event lari telah berhasil diverifikasi oleh sistem.</p>
                
                <table style='width:100%;margin:16px 0;background:#f8fafc;padding:12px;border-radius:6px;font-size:14px;'>
                    <tr><td><strong>No. Invoice</strong></td><td>: {$transaction->invoice_number}</td></tr>
                    <tr><td><strong>Status</strong></td><td>: <span style='color:#16a34a;font-weight:bold;'>LUNAS (PAID)</span></td></tr>
                    <tr><td><strong>Metode Bayar</strong></td><td>: {$transaction->payment_method}</td></tr>
                    <tr><td><strong>Waktu Bayar</strong></td><td>: {$transaction->paid_at}</td></tr>
                </table>

                <h4 style='margin-top:20px;margin-bottom:8px;'>Rincian Tiket:</h4>
                <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                    <thead>
                        <tr style='background:#f1f5f9;text-align:left;'>
                            <th style='padding:8px;'>Kategori</th>
                            <th style='padding:8px;text-align:center;'>Qty</th>
                            <th style='padding:8px;text-align:right;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>{$itemsHtml}</tbody>
                    <tfoot>
                        <tr>
                            <td colspan='2' style='padding:10px 8px;font-weight:bold;text-align:right;'>Grand Total:</td>
                            <td style='padding:10px 8px;font-weight:bold;text-align:right;color:#0f172a;'>Rp " . number_format($transaction->grand_total, 0, ',', '.') . "</td>
                        </tr>
                    </tfoot>
                </table>

                <h4 style='margin-top:24px;margin-bottom:8px;'>Daftar Pelari Terdaftar:</h4>
                <ul style='font-size:14px;padding-left:20px;'>{$participantsList}</ul>

                <div style='margin-top:24px;padding:16px;background:#eff6ff;border-left:4px solid #3b82f6;border-radius:4px;font-size:13px;'>
                    <strong>Pengambilan Racepack (RPC):</strong><br>
                    Lokasi: {$event->rpc_location}<br>
                    Jadwal: " . ($event->rpc_start_date ? $event->rpc_start_date->format('d M Y') : '-') . " s/d " . ($event->rpc_end_date ? $event->rpc_end_date->format('d M Y') : '-') . "<br>
                    Harap membawa kartu identitas asli dan menunjukkan E-Ticket ini saat penukaran.
                </div>
            </div>
            <div style='background:#f8fafc;padding:12px;text-align:center;font-size:12px;color:#64748b;'>
                Email ini dikirimkan secara otomatis oleh sistem pendaftaran resmi {$event->title}.
            </div>
        </div>";
    }

    protected function buildTicketHtml(Participant $participant): string
    {
        $transaction = $participant->transaction;
        $event = $transaction->event;
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($participant->qr_code_hash);

        return "
        <div style='font-family:Arial,sans-serif;max-width:550px;margin:auto;border:2px solid #0284c7;border-radius:12px;overflow:hidden;'>
            <div style='background:#0284c7;color:#fff;padding:18px;text-align:center;'>
                <h3 style='margin:0;letter-spacing:1px;'>OFFICIAL RUNNER E-TICKET</h3>
                <p style='margin:4px 0 0 0;font-size:14px;'>{$event->title}</p>
            </div>
            <div style='padding:20px;text-align:center;background:#fff;'>
                <img src='{$qrUrl}' alt='QR Code Tiket' style='width:160px;height:160px;margin-bottom:12px;' />
                <div style='font-size:18px;font-weight:bold;color:#0f172a;letter-spacing:2px;'>{$participant->ticket_code}</div>
                <div style='font-size:13px;color:#64748b;margin-top:2px;'>Scan QR untuk pengambilan racepack di lokasi</div>

                <div style='text-align:left;margin-top:20px;border-top:1px dashed #cbd5e1;padding-top:16px;'>
                    <table style='width:100%;font-size:14px;line-height:1.6;'>
                        <tr><td style='width:40%;color:#64748b;'>Nama Lengkap</td><td>: <strong>{$participant->full_name}</strong></td></tr>
                        <tr><td style='color:#64748b;'>Nama di BIB</td><td>: <strong>{$participant->bib_name}</strong></td></tr>
                        <tr><td style='color:#64748b;'>Kategori Lomba</td><td>: <strong>{$participant->ticketCategory->name}</strong></td></tr>
                        <tr><td style='color:#64748b;'>Ukuran Jersey</td><td>: <strong>{$participant->jerseySize->size_code}</strong></td></tr>
                        <tr><td style='color:#64748b;'>Golongan Darah</td><td>: {$participant->blood_type}</td></tr>
                        <tr><td style='color:#64748b;'>Kontak Darurat</td><td>: {$participant->emergency_contact_name} ({$participant->emergency_contact_phone})</td></tr>
                        <tr><td style='color:#64748b;'>Tanggal Race</td><td>: " . ($event->race_date ? $event->race_date->format('d F Y') : '-') . "</td></tr>
                        <tr><td style='color:#64748b;'>Lokasi Flag-Off</td><td>: {$event->venue_name}</td></tr>
                    </table>
                </div>
            </div>
            <div style='background:#f1f5f9;padding:12px;text-align:center;font-size:12px;color:#475569;'>
                Harap simpan tiket ini di ponsel Anda untuk ditunjukkan kepada panitia di lokasi lomba.
            </div>
        </div>";
    }
}
