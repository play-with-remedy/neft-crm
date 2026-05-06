<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evening_staff', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evening_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('host_id')
                ->constrained('hosts')
                ->cascadeOnDelete();

            $table->string('role');

            $table->integer('salary')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evening_staff');
    }
};
