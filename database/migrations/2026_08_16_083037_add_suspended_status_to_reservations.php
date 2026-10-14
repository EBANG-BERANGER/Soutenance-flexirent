<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lets an admin temporarily pause an active (confirmed) reservation (e.g.
 * the vehicle needs unscheduled maintenance mid-rental) and resume it
 * later, without cancelling the booking outright.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending','confirmed','cancelled','completed','suspended') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE reservations SET status = 'cancelled' WHERE status = 'suspended'");
        DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending'");
    }
};
