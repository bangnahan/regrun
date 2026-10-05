<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'code',
        'description',
        'price',
        'early_bird_price',
        'early_bird_end_date',
        'quota',
        'sold_count',
        'reserved_count',
        'min_age',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'early_bird_price' => 'decimal:2',
        'early_bird_end_date' => 'datetime',
        'quota' => 'integer',
        'sold_count' => 'integer',
        'reserved_count' => 'integer',
        'min_age' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Check if early bird pricing is currently active.
     */
    public function getIsEarlyBirdActiveAttribute(): bool
    {
        return !is_null($this->early_bird_price)
            && !is_null($this->early_bird_end_date)
            && now()->lte($this->early_bird_end_date);
    }

    /**
     * Get current price (either early bird or regular price).
     */
    public function getCurrentPriceAttribute(): float
    {
        if ($this->is_early_bird_active) {
            return (float) $this->early_bird_price;
        }

        return (float) $this->price;
    }

    /**
     * Remaining available quota.
     */
    public function getRemainingQuotaAttribute(): int
    {
        return max(0, $this->quota - ($this->sold_count + $this->reserved_count));
    }

    /**
     * Check if category can still be purchased.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->remaining_quota > 0;
    }
}
