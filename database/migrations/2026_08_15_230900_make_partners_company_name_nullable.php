<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Individual (non-agency) partners are allowed to register without a
 * company name (Point 1 of the partner-profile spec), but the column was
 * left NOT NULL, so every individual-partner signup was failing with a fatal
 * SQL error. This backfills existing rows (there shouldn't be any real
 * blank ones yet) and relaxes the constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE partners MODIFY company_name VARCHAR(255) NULL");
    }

    public function down(): void
    {
        DB::statement("UPDATE partners SET company_name = 'Unknown' WHERE company_name IS NULL");
        DB::statement("ALTER TABLE partners MODIFY company_name VARCHAR(255) NOT NULL");
    }
};
