<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
