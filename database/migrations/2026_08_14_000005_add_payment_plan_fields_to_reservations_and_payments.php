<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('payment_type', ['full', 'deposit'])->nullable()->after('total_price');
            $table->enum('payment_status', ['unpaid', 'deposit_paid', 'paid'])->default('unpaid')->after('payment_type');
            $table->decimal('original_total_price', 10, 2)->nullable()->after('payment_status');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('original_total_price');
        });

        DB::statement('UPDATE reservations SET original_total_price = total_price WHERE original_total_price IS NULL');

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('type', ['deposit', 'balance', 'full', 'change_fee'])->default('full')->after('method');
            $table->string('ussd_code')->nullable()->after('transaction_ref');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'payment_status', 'original_total_price', 'deposit_amount']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['type', 'ussd_code']);
        });
    }
};
