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
        Schema::create('players', function (Blueprint $table) {
            $table->id();

            $table->string('nickname');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('gender');

            $table->unsignedTinyInteger('birth_day');
            $table->unsignedTinyInteger('birth_month');
            $table->unsignedSmallInteger('birth_year')->nullable();

            $table->string('phone')->nullable();
            $table->string('telegram')->nullable();

            $table->foreignId('source_id')->nullable()->constrained('sources');

            $table->date('first_visit_at')->nullable();

            $table->foreignId('first_host_id')->nullable()->constrained('hosts');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
