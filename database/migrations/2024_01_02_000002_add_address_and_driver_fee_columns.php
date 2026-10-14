<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the fields the rental contract needs to state full renter identity
 * (physical address alongside name, email, phone) and the per-day driver
 * fee used in the booking charge breakdown (Ch.5.4.3 / 5.4.7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address')->nullable()->after('phone');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('driver_fee_per_day', 10, 2)->default(0)->after('driver_available');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('driver_fee_per_day');
        });
    }
};
