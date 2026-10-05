<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

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
