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
        'hero_image',
        'logo_url',
        'primary_color',
        'secondary_color',
        'custom_css',
        'custom_domain',
        'auto_generate_bib',
        'is_active',
        'is_default',
        'tripay_merchant_code',
        'tripay_api_key',
        'tripay_private_key',
        'mailketing_api_token',
        'mailketing_sender_email',
        'mailketing_sender_name',
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

    public function domains(): HasMany
    {
        return $this->hasMany(EventDomain::class)->orderByDesc('is_primary');
    }

    /**
     * Get the primary domain for this event
     */
    public function getPrimaryDomain(): ?string
    {
        $primary = $this->domains()->where('is_primary', true)->where('is_active', true)->first();
        if ($primary) {
            return $primary->domain;
        }

        if ($this->custom_domain) {
            return $this->custom_domain;
        }

        $first = $this->domains()->where('is_active', true)->first();
        return $first?->domain;
    }

    /**
     * Resolve active event by slug, domain alias, custom_domain, or default
     */
    public static function getActiveEvent(?string $slug = null, ?string $domain = null): ?self
    {
        // 1. By Slug URL (e.g. /event/{slug})
        if ($slug) {
            $event = self::where('slug', $slug)->where('is_active', true)->first();
            if ($event) {
                return $event;
            }
        }

        // 2. By Host / Domain Name
        if ($domain) {
            $cleanDomain = EventDomain::normalizeDomain($domain);

            // A. Check in event_domains table
            $domainRecord = EventDomain::where('domain', $cleanDomain)
                ->where('is_active', true)
                ->with('event')
                ->first();

            if ($domainRecord && $domainRecord->event && $domainRecord->event->is_active) {
                return $domainRecord->event;
            }

            // B. Also check without/with leading 'www.'
            $altDomain = str_starts_with($cleanDomain, 'www.')
                ? substr($cleanDomain, 4)
                : 'www.' . $cleanDomain;

            $domainRecordAlt = EventDomain::where('domain', $altDomain)
                ->where('is_active', true)
                ->with('event')
                ->first();

            if ($domainRecordAlt && $domainRecordAlt->event && $domainRecordAlt->event->is_active) {
                return $domainRecordAlt->event;
            }

            // C. Fallback: Check custom_domain column in events table
            $byCustomDomain = self::where('custom_domain', $cleanDomain)
                ->orWhere('custom_domain', $altDomain)
                ->where('is_active', true)
                ->first();

            if ($byCustomDomain) {
                return $byCustomDomain;
            }
        }

        // 3. Fallback: Default event or first active event
        return self::where('is_default', true)->where('is_active', true)->first()
            ?? self::where('is_active', true)->first();
    }

    /**
     * Resolve Tripay credentials with event-level override or global fallback
     */
    public function getTripayCredentials(): array
    {
        $mode = SystemSetting::get('tripay_mode', SystemSetting::get('tripay_sandbox', true) ? 'sandbox' : 'production');
        $isSandbox = ($mode === 'sandbox');

        // Check if event has custom credentials
        if (!empty($this->tripay_merchant_code) && !empty($this->tripay_api_key)) {
            return [
                'merchant_code' => $this->tripay_merchant_code,
                'api_key' => $this->tripay_api_key,
                'private_key' => $this->tripay_private_key,
                'is_custom' => true,
                'is_sandbox' => $isSandbox,
            ];
        }

        // Global system settings
        if ($isSandbox) {
            $merchant = (string) (SystemSetting::get('tripay_sandbox_merchant_code') ?: SystemSetting::get('tripay_merchant_code', config('services.tripay.merchant_code', 'T39430')));
            $apiKey = (string) (SystemSetting::get('tripay_sandbox_api_key') ?: SystemSetting::get('tripay_api_key', config('services.tripay.api_key', 'DEV-KTItaLxH6EY0VqEkbWrPFgkM8yunO9Btd7bMmNMi')));
            $privKey = (string) (SystemSetting::get('tripay_sandbox_private_key') ?: SystemSetting::get('tripay_private_key', config('services.tripay.private_key', 'yNQJm-Ozybz-wRDDa-ncqiY-PZ280')));
        } else {
            $merchant = (string) (SystemSetting::get('tripay_prod_merchant_code') ?: SystemSetting::get('tripay_merchant_code', config('services.tripay.merchant_code', '')));
            $apiKey = (string) (SystemSetting::get('tripay_prod_api_key') ?: SystemSetting::get('tripay_api_key', config('services.tripay.api_key', '')));
            $privKey = (string) (SystemSetting::get('tripay_prod_private_key') ?: SystemSetting::get('tripay_private_key', config('services.tripay.private_key', '')));
        }

        return [
            'merchant_code' => $merchant,
            'api_key' => $apiKey,
            'private_key' => $privKey,
            'is_custom' => false,
            'is_sandbox' => $isSandbox,
        ];
    }

    /**
     * Resolve Mailketing credentials with event-level override or global fallback
     */
    public function getMailketingCredentials(): array
    {
        if (!empty($this->mailketing_api_token)) {
            return [
                'api_token' => $this->mailketing_api_token,
                'sender_email' => $this->mailketing_sender_email ?: SystemSetting::get('mailketing_sender_email', config('services.mailketing.sender_email', 'hi@jelatix.com')),
                'sender_name' => $this->mailketing_sender_name ?: ($this->title . ' Organizing Team'),
                'is_custom' => true,
            ];
        }

        return [
            'api_token' => (string) SystemSetting::get('mailketing_api_token', config('services.mailketing.api_token', '')),
            'sender_email' => (string) SystemSetting::get('mailketing_sender_email', config('services.mailketing.sender_email', 'hi@jelatix.com')),
            'sender_name' => (string) SystemSetting::get('mailketing_sender_name', config('services.mailketing.sender_name', 'Panitia Event Lari')),
            'is_custom' => false,
        ];
    }

    /**
     * Get the resolved public URL for the hero image.
     * Falls back to banner_image if hero_image is not set.
     */
    public function getHeroImageUrlAttribute(): ?string
    {
        $image = $this->hero_image ?: $this->banner_image;
        if (empty($image)) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return asset($image);
    }
}
