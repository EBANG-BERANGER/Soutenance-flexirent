<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields required by the real CAR RENTAL AGREEMENT template (chassis
 * number and security deposit on the vehicle side; ID and driving
 * license number captured at booking time on the reservation side).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('chassis_number')->nullable()->after('plate_number');
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('driver_fee_per_day');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('client_id');
            $table->string('driving_license_number')->nullable()->after('id_number');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['chassis_number', 'deposit_amount']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['id_number', 'driving_license_number']);
        });
    }
};
