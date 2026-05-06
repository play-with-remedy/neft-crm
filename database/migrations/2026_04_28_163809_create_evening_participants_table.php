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
        Schema::create('evening_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evening_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_type_id')->constrained('payment_types')->restrictOnDelete();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->boolean('is_new_player')->default(false);
            $table->boolean('is_full_payment')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['evening_id', 'player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evening_participants');
    }
};
