<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The full, dated history behind a client's reliability_score, so a
 * disputed score isn't just a number: every point gained or lost traces
 * back to a specific event, reason and (where relevant) reservation. No
 * updated_at: an event is a fact about the past and is never edited,
 * same convention as AdminActivityLog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reliability_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');
            $table->string('reason');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reliability_events');
    }
};
