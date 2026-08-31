<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autumn_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('autumn_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->date('started_at');
            $table->date('deadline_at');
            $table->date('qualified_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['autumn_campaign_id', 'player_id', 'number']);
            $table->index(['autumn_campaign_id', 'deadline_at']);
            $table->index(['qualified_at', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autumn_cases');
    }
};
