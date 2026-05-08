<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenings', function (Blueprint $table) {
            $table->id();
            $table->timestamp('played_at');

            $table->foreignId('evening_type_id')
                ->nullable()
                ->constrained('evening_types')
                ->nullOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects')
                ->nullOnDelete();

            $table->integer('other_expenses')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenings');
    }
};