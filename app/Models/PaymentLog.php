<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $transaction_id
 * @property string|null $tripay_reference
 * @property string $event_type
 * @property string|null $signature
 * @property array<string, mixed>|null $raw_payload
 * @property array<string, mixed>|null $raw_response
 * @property int|null $http_status
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Transaction|null $transaction
 */
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

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
