<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->string('event_id')->nullable();
            $table->string('type'); // deposit, withdrawal, unknown_change, manual_adjust
            $table->integer('amount'); // signed: deposit +, withdrawal -, unknown_change 0, manual +/-
            $table->integer('original_amount')->nullable();
            $table->integer('coin_value')->nullable();
            $table->decimal('weight_delta_g', 8, 3)->nullable();
            $table->string('source'); // device, web
            $table->dateTime('occurred_at');
            $table->dateTime('received_at');
            $table->boolean('time_estimated')->default(false);
            $table->boolean('needs_review')->default(false);
            $table->string('status')->default('active'); // active, voided
            $table->dateTime('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'event_id']);
            $table->index(['device_id', 'occurred_at']);
            $table->index(['device_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

