<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evening_staff', function (Blueprint $table): void {
            $table->decimal('salary', 12, 2)->default(0)->change();
        });

        Schema::table('evenings', function (Blueprint $table): void {
            $table->decimal('other_expenses', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('evening_staff', function (Blueprint $table): void {
            $table->integer('salary')->default(0)->change();
        });

        Schema::table('evenings', function (Blueprint $table): void {
            $table->integer('other_expenses')->default(0)->change();
        });
    }
};
