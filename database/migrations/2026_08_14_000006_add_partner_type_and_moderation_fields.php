<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->enum('partner_type', ['agency', 'individual'])->default('agency')->after('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('is_suspended');
        });

        Schema::create('banned_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('platform_commission', 10, 2)->nullable()->after('deposit_amount');
            $table->decimal('owner_payout', 10, 2)->nullable()->after('platform_commission');
        });

        DB::statement("UPDATE vehicles SET chassis_number = 'UNKNOWN' WHERE chassis_number IS NULL OR chassis_number = ''");
        DB::statement('ALTER TABLE vehicles MODIFY chassis_number VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('partner_type');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('banned_at');
        });

        Schema::dropIfExists('banned_contacts');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['platform_commission', 'owner_payout']);
        });

        DB::statement('ALTER TABLE vehicles MODIFY chassis_number VARCHAR(255) NULL');
    }
};
