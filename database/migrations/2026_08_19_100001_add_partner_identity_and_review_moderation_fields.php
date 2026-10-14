<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('national_id_number')->nullable()->after('tin');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->text('moderation_reason')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('national_id_number');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('moderation_reason');
        });
    }
};
