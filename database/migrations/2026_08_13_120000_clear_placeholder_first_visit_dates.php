<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('players')
            ->whereDate('first_visit_at', '2026-01-01')
            ->whereNotExists(function (Builder $query): void {
                $query
                    ->selectRaw('1')
                    ->from('evening_participants')
                    ->whereColumn('evening_participants.player_id', 'players.id');
            })
            ->update(['first_visit_at' => null]);
    }

    public function down(): void
    {
        // The placeholder date cannot be safely distinguished from an intentionally empty date.
    }
};
