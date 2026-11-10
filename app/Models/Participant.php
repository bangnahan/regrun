<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $ticket_category_id
 * @property int|null $jersey_size_id
 * @property string $ticket_code
 * @property string|null $bib_number
 * @property string $full_name
 * @property string $identity_number
 * @property string $gender
 * @property Carbon|null $date_of_birth
 * @property string $phone_number
 * @property string $email
 * @property string|null $blood_type
 * @property string|null $bib_name
 * @property string $emergency_contact_name
 * @property string $emergency_contact_phone
 * @property string $emergency_contact_relation
 * @property string|null $medical_notes
 * @property string|null $running_club
 * @property bool $is_racepack_collected
 * @property Carbon|null $racepack_collected_at
 * @property int|null $racepack_collected_by
 * @property string|null $qr_code_hash
 * @property string|null $qr_code_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Transaction $transaction
 * @property-read TicketCategory $ticketCategory
 * @property-read JerseySize|null $jerseySize
 * @property-read User|null $racepackCollector
 * @property-read Collection<int, EmailLog> $emailLogs
 */
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

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<TicketCategory, $this>
     */
    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    /**
     * @return BelongsTo<JerseySize, $this>
     */
    public function jerseySize(): BelongsTo
    {
        return $this->belongsTo(JerseySize::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function racepackCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'racepack_collected_by');
    }

    /**
     * @return HasMany<EmailLog, $this>
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Generate unique ticket code and QR code hash.
     */
    public static function generateUniqueCodes(): array
    {
        $ticketCode = 'TKT-'.strtoupper(Str::random(6));
        while (self::where('ticket_code', $ticketCode)->exists()) {
            $ticketCode = 'TKT-'.strtoupper(Str::random(6));
        }

        // Isi data QR Code dibuat sama persis dengan Nomor Tiket agar mudah discan oleh scanner panitia
        $qrCodeHash = $ticketCode;

        return [$ticketCode, $qrCodeHash];
    }
}
