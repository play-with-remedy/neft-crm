<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('evening_participants', 'is_new_player')) {
            return;
        }

        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->dropColumn('is_new_player');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('evening_participants', 'is_new_player')) {
            return;
        }

        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->boolean('is_new_player')->default(false);
        });
    }
};
