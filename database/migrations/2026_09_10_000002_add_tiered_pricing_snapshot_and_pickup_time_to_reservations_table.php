<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freezes the vehicle's weekly/monthly rate onto the reservation at booking
 * time, same reasoning as price_per_day_snapshot (see
 * 2026_08_21_000001_add_price_snapshot_to_reservations_table): a later
 * partner price change must never retroactively change what an existing
 * client owes. Also adds pickup_time, since pickup/return only happens
 * between 08:00 and 23:00 and the client now picks a time within that
 * window at booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('price_per_week_snapshot', 10, 2)->nullable()->after('price_per_day_snapshot');
            $table->decimal('price_per_month_snapshot', 10, 2)->nullable()->after('price_per_week_snapshot');
            $table->time('pickup_time')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['price_per_week_snapshot', 'price_per_month_snapshot', 'pickup_time']);
        });
    }
};
