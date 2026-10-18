<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->date('received_at');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('telegram')->nullable();
            $table->string('source_channel')->nullable();
            $table->string('stage')->nullable();
            $table->timestamps();

            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
