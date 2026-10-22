<?php

namespace Tests\Feature;

use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayerDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_button_is_shown_before_cancel_button(): void
    {
        $player = Player::query()->create(['nickname' => 'Игрок']);

        $component = Livewire::test(EditPlayer::class, [
            'record' => $player->getRouteKey(),
        ]);

        $deleteAction = $component->instance()->getAction('delete');

        $this->assertNotNull($deleteAction);
        $this->assertSame(
            ['submit', 'cancel'],
            array_keys($deleteAction->getModalFooterActions()),
        );
    }
}
