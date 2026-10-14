<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traffic violations ("contraventions") a partner reports against their own
 * vehicle: reference number(s), the violation date, and its payment
 * deadline. reservation_id is resolved automatically (see Violation::
 * resolveReservation()) by matching violation_date against a completed or
 * confirmed rental on that vehicle, even if the rental has since ended, so
 * the client responsible at that time can be notified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('reference_number');
            $table->date('violation_date');
            $table->date('payment_due_date');
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'violation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};
