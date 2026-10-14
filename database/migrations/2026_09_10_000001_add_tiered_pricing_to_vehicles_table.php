<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional weekly/monthly rates alongside the required price_per_day.
 * Nullable: a vehicle with neither set is billed entirely at the day rate,
 * exactly as before (see App\Support\RentalPricing).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('price_per_week', 10, 2)->nullable()->after('price_per_day');
            $table->decimal('price_per_month', 10, 2)->nullable()->after('price_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['price_per_week', 'price_per_month']);
        });
    }
};
