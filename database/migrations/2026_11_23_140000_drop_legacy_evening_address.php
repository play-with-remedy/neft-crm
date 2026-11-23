<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenings', function (Blueprint $table): void {
            $table->dropColumn('address');
        });
    }

    public function down(): void
    {
        Schema::table('evenings', function (Blueprint $table): void {
            $table->string('address', 500)->nullable();
        });
    }
};
