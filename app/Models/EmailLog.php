<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $transaction_id
 * @property int|null $participant_id
 * @property string $recipient_email
 * @property string $email_type
 * @property string|null $mailketing_message_id
 * @property string $status
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Transaction|null $transaction
 * @property-read Participant|null $participant
 */
class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'participant_id',
        'recipient_email',
        'email_type',
        'mailketing_message_id',
        'status',
        'error_message',
    ];

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
