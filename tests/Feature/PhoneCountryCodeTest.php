<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Players\Pages\CreatePlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneCountryCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_mask_changes_with_the_selected_country_code(): void
    {
        Livewire::test(CreatePlayer::class)
            ->assertSeeHtml('placeholder="XX XXXXXXX"')
            ->assertSeeHtml('x-mask="99 9999999"')
            ->set('data.phone_country_code', '7')
            ->assertSeeHtml('placeholder="XXX XXXXXXX"')
            ->assertSeeHtml('x-mask="999 9999999"');
    }

    public function test_russian_phone_can_be_saved_for_a_lead(): void
    {
        Livewire::test(ListLeads::class)
            ->callAction('create', data: [
                'received_at' => '2026-10-18',
                'name' => 'Анна',
                'phone_country_code' => '7',
                'phone' => '999 1234567',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'name' => 'Анна',
            'phone' => '+79991234567',
        ]);
    }

    public function test_russian_phone_can_be_saved_for_a_player(): void
    {
        Livewire::test(CreatePlayer::class)
            ->fillForm([
                'nickname' => 'Лиса',
                'phone_country_code' => '7',
                'phone' => '999 1234567',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('players', [
            'nickname' => 'Лиса',
            'phone' => '+79991234567',
        ]);
    }
}
