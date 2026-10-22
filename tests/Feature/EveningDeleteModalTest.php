<?php

namespace Tests\Feature;

use App\Filament\Resources\Evenings\Pages\EditEvening;
use App\Models\Evening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EveningDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_button_is_shown_before_cancel_button(): void
    {
        $evening = Evening::query()->create(['played_at' => now()]);

        $component = Livewire::test(EditEvening::class, [
            'record' => $evening->getRouteKey(),
        ]);

        $deleteAction = $component->instance()->getAction('delete');

        $this->assertNotNull($deleteAction);
        $this->assertSame(
            ['submit', 'cancel'],
            array_keys($deleteAction->getModalFooterActions()),
        );
    }
}
