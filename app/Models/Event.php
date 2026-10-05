<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'venue_name',
        'venue_address',
        'race_date',
        'race_start_time',
        'rpc_start_date',
        'rpc_end_date',
        'rpc_location',
        'banner_image',
        'custom_domain',
        'auto_generate_bib',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'race_date' => 'date',
        'rpc_start_date' => 'date',
        'rpc_end_date' => 'date',
        'auto_generate_bib' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function ticketCategories(): HasMany
    {
        return $this->hasMany(TicketCategory::class)->orderBy('sort_order');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function participants(): HasManyThrough
    {
        return $this->hasManyThrough(Participant::class, Transaction::class);
    }

    public static function getActiveEvent(?string $slug = null, ?string $domain = null): ?self
    {
        if ($slug) {
            return self::where('slug', $slug)->where('is_active', true)->first();
        }

        if ($domain) {
            $byDomain = self::where('custom_domain', $domain)->where('is_active', true)->first();
            if ($byDomain) {
                return $byDomain;
            }
        }

        return self::where('is_default', true)->where('is_active', true)->first()
            ?? self::where('is_active', true)->first();
    }
}
