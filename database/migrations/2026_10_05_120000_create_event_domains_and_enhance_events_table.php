<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create event_domains table for multi-domain & domain aliases support
        Schema::create('event_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('domain')->unique(); // e.g. 'tiket.nusantararun.com', 'nusantararun.com'
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('ssl_verified_at')->nullable();
            $table->timestamps();

            $table->index(['domain', 'is_active']);
        });

        // 2. Enhance events table with branding and optional gateway credentials override
        Schema::table('events', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('banner_image');
            $table->string('primary_color', 20)->default('#ea580c')->after('logo_url');
            $table->string('secondary_color', 20)->default('#0f172a')->after('primary_color');
            $table->text('custom_css')->nullable()->after('secondary_color');

            // Event-level gateway credentials override (Optional per-event merchant accounts)
            $table->string('tripay_merchant_code', 50)->nullable()->after('auto_generate_bib');
            $table->string('tripay_api_key')->nullable()->after('tripay_merchant_code');
            $table->string('tripay_private_key')->nullable()->after('tripay_api_key');
            $table->string('mailketing_api_token')->nullable()->after('tripay_private_key');
            $table->string('mailketing_sender_email')->nullable()->after('mailketing_api_token');
            $table->string('mailketing_sender_name')->nullable()->after('mailketing_sender_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_domains');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'logo_url',
                'primary_color',
                'secondary_color',
                'custom_css',
                'tripay_merchant_code',
                'tripay_api_key',
                'tripay_private_key',
                'mailketing_api_token',
                'mailketing_sender_email',
                'mailketing_sender_name',
            ]);
        });
    }
};
