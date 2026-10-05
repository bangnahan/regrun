<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'ticket_category_id',
        'jersey_size_id',
        'ticket_code',
        'bib_number',
        'full_name',
        'identity_number',
        'gender',
        'date_of_birth',
        'phone_number',
        'email',
        'blood_type',
        'bib_name',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'medical_notes',
        'running_club',
        'is_racepack_collected',
        'racepack_collected_at',
        'racepack_collected_by',
        'qr_code_hash',
        'qr_code_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_racepack_collected' => 'boolean',
        'racepack_collected_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    public function jerseySize(): BelongsTo
    {
        return $this->belongsTo(JerseySize::class);
    }

    public function racepackCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'racepack_collected_by');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Generate unique ticket code and QR code hash.
     */
    public static function generateUniqueCodes(): array
    {
        $ticketCode = 'TKT-' . strtoupper(Str::random(6));
        while (self::where('ticket_code', $ticketCode)->exists()) {
            $ticketCode = 'TKT-' . strtoupper(Str::random(6));
        }

        // Isi data QR Code dibuat sama persis dengan Nomor Tiket agar mudah discan oleh scanner panitia
        $qrCodeHash = $ticketCode;

        return [$ticketCode, $qrCodeHash];
    }
}
