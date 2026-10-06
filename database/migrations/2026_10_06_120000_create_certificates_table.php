<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->date('purchased_at');
            $table->string('type');
            $table->string('purchaser_nickname');
            $table->date('used_at')->nullable();
            $table->timestamps();

            $table->index('purchased_at');
            $table->index('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
