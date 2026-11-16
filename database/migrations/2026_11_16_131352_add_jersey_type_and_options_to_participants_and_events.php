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
        Schema::table('participants', function (Blueprint $table) {
            $table->string('jersey_type', 20)->default('short')->after('jersey_size_id');
            $table->decimal('jersey_surcharge', 10, 2)->default(0.00)->after('jersey_type');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('allow_jersey_options')->default(true)->after('auto_generate_bib');
            $table->boolean('allow_singlet')->default(true)->after('allow_jersey_options');
            $table->boolean('allow_long_sleeve')->default(true)->after('allow_singlet');
            $table->decimal('long_sleeve_price', 10, 2)->default(20000.00)->after('allow_long_sleeve');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['jersey_type', 'jersey_surcharge']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['allow_jersey_options', 'allow_singlet', 'allow_long_sleeve', 'long_sleeve_price']);
        });
    }
};
