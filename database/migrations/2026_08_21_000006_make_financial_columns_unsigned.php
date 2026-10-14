<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every money column becomes UNSIGNED, so a negative total_price/amount/
 * payout is rejected by MySQL itself instead of relying only on
 * application-level validation. Raw SQL (not Blueprint::unsigned()->change())
 * since this project doesn't depend on doctrine/dbal, matching the existing
 * raw-ALTER convention used for the reservations.status enum change.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE reservations
                MODIFY total_price DECIMAL(10,2) UNSIGNED NOT NULL,
                MODIFY delivery_fee DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
                MODIFY price_per_day_snapshot DECIMAL(10,2) UNSIGNED NULL,
                MODIFY driver_fee_per_day_snapshot DECIMAL(10,2) UNSIGNED NULL,
                MODIFY vehicle_deposit_snapshot DECIMAL(10,2) UNSIGNED NULL,
                MODIFY original_total_price DECIMAL(10,2) UNSIGNED NULL,
                MODIFY deposit_amount DECIMAL(10,2) UNSIGNED NULL,
                MODIFY platform_commission DECIMAL(10,2) UNSIGNED NULL,
                MODIFY owner_payout DECIMAL(10,2) UNSIGNED NULL
        ');

        DB::statement('
            ALTER TABLE payments
                MODIFY amount DECIMAL(10,2) UNSIGNED NOT NULL
        ');

        DB::statement('
            ALTER TABLE vehicles
                MODIFY price_per_day DECIMAL(10,2) UNSIGNED NOT NULL,
                MODIFY driver_fee_per_day DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
                MODIFY deposit_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE reservations
                MODIFY total_price DECIMAL(10,2) NOT NULL,
                MODIFY delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
                MODIFY price_per_day_snapshot DECIMAL(10,2) NULL,
                MODIFY driver_fee_per_day_snapshot DECIMAL(10,2) NULL,
                MODIFY vehicle_deposit_snapshot DECIMAL(10,2) NULL,
                MODIFY original_total_price DECIMAL(10,2) NULL,
                MODIFY deposit_amount DECIMAL(10,2) NULL,
                MODIFY platform_commission DECIMAL(10,2) NULL,
                MODIFY owner_payout DECIMAL(10,2) NULL
        ');

        DB::statement('
            ALTER TABLE payments
                MODIFY amount DECIMAL(10,2) NOT NULL
        ');

        DB::statement('
            ALTER TABLE vehicles
                MODIFY price_per_day DECIMAL(10,2) NOT NULL,
                MODIFY driver_fee_per_day DECIMAL(10,2) NOT NULL DEFAULT 0,
                MODIFY deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0
        ');
    }
};
