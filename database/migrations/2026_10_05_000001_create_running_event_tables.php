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
        // 1. Events Table (Multi-event support)
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('venue_name');
            $table->text('venue_address')->nullable();
            $table->date('race_date');
            $table->time('race_start_time')->default('06:00:00');
            $table->date('rpc_start_date')->nullable();
            $table->date('rpc_end_date')->nullable();
            $table->text('rpc_location')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('custom_domain')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 2. Ticket Categories Table
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name'); // e.g. "5K Fun Run", "10K Open"
            $table->string('code', 20); // e.g. "5K", "10K"
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('early_bird_price', 12, 2)->nullable();
            $table->dateTime('early_bird_end_date')->nullable();
            $table->unsignedInteger('quota')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->unsignedInteger('reserved_count')->default(0);
            $table->unsignedSmallInteger('min_age')->default(12);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Jersey Sizes Table (XS to 5XL)
        Schema::create('jersey_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('size_code', 10)->unique(); // XS, S, M, L, XL, XXL, 3XL, 4XL, 5XL
            $table->string('label', 100);
            $table->string('gender_cut', 20)->default('unisex');
            $table->unsignedSmallInteger('chest_width_cm')->nullable();
            $table->unsignedSmallInteger('body_length_cm')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 4. Transactions Table (Tripay Closed Payment)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->string('tripay_reference', 60)->nullable()->index();
            $table->string('tripay_merchant_ref', 60)->unique();
            $table->string('buyer_name', 120);
            $table->string('buyer_email', 150)->index();
            $table->string('buyer_phone', 30);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('payment_method', 80)->nullable();
            $table->string('payment_channel_code', 30)->nullable();
            $table->text('tripay_checkout_url')->nullable();
            $table->string('tripay_pay_code', 100)->nullable();
            $table->text('tripay_qr_url')->nullable();
            $table->enum('status', ['UNPAID', 'PAID', 'EXPIRED', 'FAILED', 'REFUNDED'])->default('UNPAID')->index();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // 5. Transaction Items Table
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('ticket_category_id')->constrained('ticket_categories');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });

        // 6. Participants Table (Runner Details)
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('ticket_category_id')->constrained('ticket_categories');
            $table->foreignId('jersey_size_id')->constrained('jersey_sizes');
            $table->string('ticket_code', 30)->unique();
            $table->string('bib_number', 20)->nullable()->index();
            $table->string('full_name', 120);
            $table->string('identity_number', 50)->index(); // NIK or Passport
            $table->enum('gender', ['L', 'P']);
            $table->date('date_of_birth');
            $table->string('phone_number', 30);
            $table->string('email', 150);
            $table->enum('blood_type', ['A', 'B', 'AB', 'O', 'UNKNOWN'])->default('UNKNOWN');
            $table->string('bib_name', 20); // Only printed on BIB
            $table->string('emergency_contact_name', 100);
            $table->string('emergency_contact_phone', 30);
            $table->string('emergency_contact_relation', 50);
            $table->text('medical_notes')->nullable();
            $table->string('running_club', 100)->nullable();
            $table->boolean('is_racepack_collected')->default(false)->index();
            $table->dateTime('racepack_collected_at')->nullable();
            $table->foreignId('racepack_collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_code_hash', 64)->unique();
            $table->string('qr_code_path')->nullable();
            $table->timestamps();
        });

        // 7. Payment Logs Table
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('tripay_reference', 60)->nullable();
            $table->string('event_type', 50); // e.g. 'callback', 'create_transaction'
            $table->string('signature')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('http_status', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // 8. Email Logs Table
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->string('recipient_email', 150);
            $table->string('email_type', 50); // 'invoice', 'eticket', 'reminder'
            $table->string('mailketing_message_id', 100)->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 9. System Settings Table
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 50)->default('general')->index();
            $table->string('key_name', 100)->unique();
            $table->text('key_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('jersey_sizes');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('events');
    }
};
