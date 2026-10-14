<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stored directly on the row (not recomputed from reliability_events on
 * every read) so checking it during payment/booking stays a cheap column
 * read. New accounts start neutral at 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reliability_score')->default(0)->after('is_suspended');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reliability_score');
        });
    }
};
