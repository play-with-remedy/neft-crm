<?php

namespace App\Services;

use App\Models\Evening;
use App\Models\EveningParticipant;

class ReportService
{
    public function getMonthlyRevenue()
    {
        return Evening::query()
            ->join('evening_participants', 'evenings.id', '=', 'evening_participants.evening_id')
            ->selectRaw("
                DATE_FORMAT(evenings.played_at, '%Y-%m-01') as month,
                SUM(evening_participants.paid_amount) as total_revenue
            ")
            ->groupByRaw("DATE_FORMAT(evenings.played_at, '%Y-%m-01')")
            ->orderByRaw("DATE_FORMAT(evenings.played_at, '%Y-%m-01') DESC")
            ->get();
    }

    public function getPlayerMonthlyRevenue()
    {
        return EveningParticipant::query()
            ->join('evenings', 'evening_participants.evening_id', '=', 'evenings.id')
            ->join('players', 'evening_participants.player_id', '=', 'players.id')
            ->selectRaw("
                players.id as player_id,
                players.nickname,
                players.first_name,
                players.last_name,
                DATE_FORMAT(evenings.played_at, '%Y-%m-01') as month,
                SUM(evening_participants.paid_amount) as total_paid
            ")
            ->groupByRaw("
                players.id,
                players.nickname,
                players.first_name,
                players.last_name,
                DATE_FORMAT(evenings.played_at, '%Y-%m-01')
            ")
            ->orderByRaw("DATE_FORMAT(evenings.played_at, '%Y-%m-01') DESC")
            ->orderBy('players.nickname')
            ->get();
    }
}