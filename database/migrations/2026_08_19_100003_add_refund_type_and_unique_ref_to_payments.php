<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY type ENUM('deposit','balance','full','change_fee','refund') NOT NULL DEFAULT 'full'");

        Schema::table('payments', function ($table) {
            $table->unique('transaction_ref');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function ($table) {
            $table->dropUnique(['transaction_ref']);
        });

        DB::statement("ALTER TABLE payments MODIFY type ENUM('deposit','balance','full','change_fee') NOT NULL DEFAULT 'full'");
    }
};
