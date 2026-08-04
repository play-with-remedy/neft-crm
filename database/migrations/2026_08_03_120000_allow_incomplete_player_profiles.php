<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->change();
            $table->string('gender')->nullable()->change();
            $table->unsignedTinyInteger('birth_day')->nullable()->change();
            $table->unsignedTinyInteger('birth_month')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table): void {
            $table->string('first_name')->nullable(false)->change();
            $table->string('gender')->nullable(false)->change();
            $table->unsignedTinyInteger('birth_day')->nullable(false)->change();
            $table->unsignedTinyInteger('birth_month')->nullable(false)->change();
        });
    }
};
