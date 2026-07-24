<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'financial_category_values',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('financial_category_id')
                    ->constrained('financial_categories')
                    ->cascadeOnDelete();

                /*
                 * Всегда храним первое число месяца:
                 * 2026-07-01, 2026-08-01 и т. д.
                 */
                $table->date('period');

                $table->decimal('amount', 15, 2)
                    ->default(0);

                $table->text('details')
                    ->nullable();

                $table->timestamps();

                /*
                 * У одной статьи может быть только одно
                 * значение за конкретный месяц.
                 */
                $table->unique(
                    ['financial_category_id', 'period'],
                    'financial_category_period_unique'
                );

                $table->index('period');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_category_values');
    }
};