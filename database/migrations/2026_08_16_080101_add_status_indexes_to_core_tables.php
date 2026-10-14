<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status columns are filtered on constantly (catalog availability, admin
 * lists, dashboard counts) but weren't indexed. Harmless at today's data
 * volume, but cheap to add now before it matters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->index('status');
        });
        Schema::table('partners', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
