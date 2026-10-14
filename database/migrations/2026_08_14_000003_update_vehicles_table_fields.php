<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('brand');
            $table->enum('propulsion', ['diesel', 'essence'])->default('essence')->after('category_id');
            $table->enum('car_type', ['normal', 'hybrid', 'electric'])->default('normal')->after('propulsion');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('brand')->after('category_id')->default('');
            $table->dropColumn(['propulsion', 'car_type']);
        });
    }
};
