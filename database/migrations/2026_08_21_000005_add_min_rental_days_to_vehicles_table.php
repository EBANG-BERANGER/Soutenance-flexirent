<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimum number of days a client must book this vehicle for. Settable by
 * both the partner (their own vehicle, from the dashboard) and admin (any
 * vehicle, from the edit form), enforced at booking time in
 * ReservationController. Defaults to 1 (no restriction) so existing
 * vehicles behave exactly as before until someone raises it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_rental_days')->default(1)->after('price_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('min_rental_days');
        });
    }
};
