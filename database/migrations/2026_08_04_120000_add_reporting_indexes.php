<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenings', function (Blueprint $table): void {
            $table->index('played_at');
            $table->index('project_id');
            $table->index('evening_type_id');
        });

        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->index('player_id');
            $table->index('payment_type_id');
        });

        Schema::table('evening_staff', function (Blueprint $table): void {
            $table->index('evening_id');
            $table->index(['host_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('evening_staff', function (Blueprint $table): void {
            $table->dropIndex(['host_id', 'role']);
            $table->dropIndex(['evening_id']);
        });

        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->dropIndex(['payment_type_id']);
            $table->dropIndex(['player_id']);
        });

        Schema::table('evenings', function (Blueprint $table): void {
            $table->dropIndex(['evening_type_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['played_at']);
        });
    }
};
