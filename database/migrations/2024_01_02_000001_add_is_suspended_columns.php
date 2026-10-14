<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a suspend/reinstate switch for the admin moderation tools
 * (Ch.5.4.6 Administrator Dashboard: user, partner and vehicle oversight).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('role');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('status');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_suspended');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('is_suspended');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('is_suspended');
        });
    }
};
