<?php

namespace Tests\Unit;

use App\Models\Player;
use PHPUnit\Framework\TestCase;

class PlayerActivityStatusTest extends TestCase
{
    public function test_season_player_manual_status_has_a_display_label(): void
    {
        $player = new Player([
            'manual_activity_status' => Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
        ]);

        $this->assertSame('Игрок сезона', $player->manual_activity_status_label);
        $this->assertSame('Игрок сезона', $player->activity_status_label);
    }

    public function test_empty_manual_status_allows_automatic_calculation(): void
    {
        $player = new Player();

        $this->assertNull($player->manual_activity_status_label);
        $this->assertSame('Гость клуба', $player->activity_status_label);
    }

    public function test_twenty_recent_visits_give_club_player_status(): void
    {
        $player = new Player();

        $this->assertSame('Гость клуба', $player->resolveActivityStatusLabel(19));
        $this->assertSame('Клубный игрок', $player->resolveActivityStatusLabel(20));
    }

    public function test_manual_status_has_priority_over_club_player_status(): void
    {
        $player = new Player([
            'manual_activity_status' => Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
        ]);

        $this->assertSame('Игрок сезона', $player->resolveActivityStatusLabel(20));
    }

    public function test_funnel_status_uses_the_shared_visit_thresholds(): void
    {
        $this->assertSame('none', Player::funnelStatusKey(0));
        $this->assertSame('new', Player::funnelStatusKey(1));
        $this->assertSame('contender', Player::funnelStatusKey(5));
        $this->assertSame('active', Player::funnelStatusKey(10));
        $this->assertSame('regular', Player::funnelStatusKey(21));
        $this->assertSame('Постоянный', Player::funnelStatusLabel('regular'));
    }
}
