<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property string $invoice_number
 * @property string|null $tripay_reference
 * @property string|null $tripay_merchant_ref
 * @property string $buyer_name
 * @property string $buyer_email
 * @property string $buyer_phone
 * @property float $subtotal
 * @property float $fee_amount
 * @property float $grand_total
 * @property string|null $payment_method
 * @property string|null $payment_channel_code
 * @property string|null $tripay_checkout_url
 * @property string|null $tripay_pay_code
 * @property string|null $tripay_qr_url
 * @property string $status
 * @property Carbon|null $paid_at
 * @property Carbon|null $expires_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read Collection<int, TransactionItem> $items
 * @property-read Collection<int, Participant> $participants
 * @property-read Collection<int, PaymentLog> $paymentLogs
 * @property-read Collection<int, EmailLog> $emailLogs
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'invoice_number',
        'tripay_reference',
        'tripay_merchant_ref',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'subtotal',
        'fee_amount',
        'grand_total',
        'payment_method',
        'payment_channel_code',
        'tripay_checkout_url',
        'tripay_pay_code',
        'tripay_qr_url',
        'status',
        'paid_at',
        'expires_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * @return HasMany<PaymentLog, $this>
     */
    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    /**
     * @return HasMany<EmailLog, $this>
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED' || ($this->status === 'UNPAID' && $this->expires_at && now()->gt($this->expires_at));
    }
}
