<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A 100%-electric vehicle has no combustion engine, so "propulsion"
 * (essence/diesel) no longer applies to it: only normal and hybrid
 * vehicles keep a propulsion value.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vehicles MODIFY propulsion ENUM('diesel', 'essence') NULL DEFAULT NULL");
        DB::statement("UPDATE vehicles SET propulsion = NULL WHERE car_type = 'electric'");
    }

    public function down(): void
    {
        DB::statement("UPDATE vehicles SET propulsion = 'essence' WHERE propulsion IS NULL");
        DB::statement("ALTER TABLE vehicles MODIFY propulsion ENUM('diesel', 'essence') NOT NULL DEFAULT 'essence'");
    }
};
