<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'domain',
        'is_primary',
        'is_active',
        'ssl_verified_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'ssl_verified_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Normalize domain string for consistent matching
     */
    public static function normalizeDomain(?string $domain): string
    {
        if (empty($domain)) {
            return '';
        }

        // 1. Remove scheme (http://, https://)
        $clean = preg_replace('#^https?://#i', '', trim($domain));

        // 2. Remove trailing slashes and paths first
        $clean = explode('/', $clean)[0];

        // 3. Remove port numbers (:8080, :443)
        $clean = preg_replace('/:\d+$/', '', $clean);

        // 4. Lowercase
        $clean = strtolower($clean);

        return trim($clean);
    }
}
