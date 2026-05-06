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
        Schema::create('evening_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evening_id')
                ->constrained('evenings')
                ->cascadeOnDelete();

            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->restrictOnDelete();

            $table->decimal('amount', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(['evening_id', 'expense_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evening_expenses');
    }
};
