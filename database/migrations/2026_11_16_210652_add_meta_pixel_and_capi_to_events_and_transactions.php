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
        Schema::table('events', function (Blueprint $table) {
            $table->string('meta_pixel_id', 50)->nullable()->after('mailketing_sender_name');
            $table->text('meta_capi_token')->nullable()->after('meta_pixel_id');
            $table->string('meta_capi_test_code', 50)->nullable()->after('meta_capi_token');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('meta_fbp', 100)->nullable()->after('user_agent');
            $table->string('meta_fbc', 150)->nullable()->after('meta_fbp');
            $table->dateTime('capi_purchase_sent_at')->nullable()->after('meta_fbc');
            $table->text('capi_purchase_response')->nullable()->after('capi_purchase_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'meta_fbp',
                'meta_fbc',
                'capi_purchase_sent_at',
                'capi_purchase_response',
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'meta_pixel_id',
                'meta_capi_token',
                'meta_capi_test_code',
            ]);
        });
    }
};
