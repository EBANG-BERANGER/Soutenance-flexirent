<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Freezes the vehicle's per-day rate, driver fee and security deposit onto
 * the reservation at booking time. Without this, the contract PDF and any
 * later recalculation (e.g. Modify Reservation) pull the vehicle's CURRENT
 * price, which can silently drift out of sync with total_price if a
 * partner edits their listing after a client already booked at the old
 * rate. Nullable + backfilled from the vehicle's current price for
 * pre-existing rows, since there's no way to know their original rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('price_per_day_snapshot', 10, 2)->nullable()->after('total_price');
            $table->decimal('driver_fee_per_day_snapshot', 10, 2)->nullable()->after('price_per_day_snapshot');
            $table->decimal('vehicle_deposit_snapshot', 10, 2)->nullable()->after('driver_fee_per_day_snapshot');
        });

        DB::statement('
            UPDATE reservations
            JOIN vehicles ON vehicles.id = reservations.vehicle_id
            SET
                reservations.price_per_day_snapshot = vehicles.price_per_day,
                reservations.driver_fee_per_day_snapshot = vehicles.driver_fee_per_day,
                reservations.vehicle_deposit_snapshot = vehicles.deposit_amount
            WHERE reservations.price_per_day_snapshot IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['price_per_day_snapshot', 'driver_fee_per_day_snapshot', 'vehicle_deposit_snapshot']);
        });
    }
};
