<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'tripay_reference',
        'event_type',
        'signature',
        'raw_payload',
        'raw_response',
        'http_status',
        'ip_address',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'raw_response' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
