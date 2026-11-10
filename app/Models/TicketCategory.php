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
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property float $price
 * @property float|null $early_bird_price
 * @property Carbon|null $early_bird_end_date
 * @property int $quota
 * @property int $sold_count
 * @property int $reserved_count
 * @property int $min_age
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read Collection<int, Participant> $participants
 */
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

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Participant, $this>
     */
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
        return ! is_null($this->early_bird_price)
            && ! is_null($this->early_bird_end_date)
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
