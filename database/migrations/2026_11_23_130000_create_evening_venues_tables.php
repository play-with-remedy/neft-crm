<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evening_venues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evening_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['evening_id', 'location_id']);
        });

        Schema::create('evening_venue_hall', function (Blueprint $table): void {
            $table->foreignId('evening_venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained()->restrictOnDelete();
            $table->primary(['evening_venue_id', 'hall_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evening_venue_hall');
        Schema::dropIfExists('evening_venues');
    }
};
