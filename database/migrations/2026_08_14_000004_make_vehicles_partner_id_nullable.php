<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
        });

        DB::statement('ALTER TABLE vehicles MODIFY partner_id BIGINT UNSIGNED NULL');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
        });

        DB::statement('ALTER TABLE vehicles MODIFY partner_id BIGINT UNSIGNED NOT NULL');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete();
        });
    }
};
