<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Players\Pages\CreatePlayer;
use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Models\Lead;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneCountryCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_created_without_phone_has_default_country_code_when_edited(): void
    {
        Livewire::test(CreatePlayer::class)
            ->fillForm(['nickname' => 'Без телефона'])
            ->call('create')
            ->assertHasNoFormErrors();

        $player = Player::query()->sole();
        $this->assertNull($player->phone);

        Livewire::test(EditPlayer::class, ['record' => $player->id])
            ->assertFormSet(['phone_country_code' => '375', 'phone' => null])
            ->fillForm(['first_name' => 'Анна'])
            ->call('save')->assertHasNoFormErrors();

        $this->assertNull($player->fresh()->phone);

        Livewire::test(EditPlayer::class, ['record' => $player->id])
            ->fillForm(['phone' => '29 1234567'])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('+375291234567', $player->fresh()->phone);
    }

    public function test_editing_existing_phone_keeps_its_country_code_and_number(): void
    {
        foreach ([['375', '29 1234567', '+375291234567'], ['7', '999 1234567', '+79991234567']] as [$code, $local, $phone]) {
            $player = Player::create(['nickname' => 'Игрок '.$code, 'phone' => $phone]);

            Livewire::test(EditPlayer::class, ['record' => $player->id])
                ->assertFormSet(['phone_country_code' => $code, 'phone' => $local])
                ->fillForm(['first_name' => 'Анна'])
                ->call('save')->assertHasNoFormErrors();

            $this->assertSame($phone, $player->fresh()->phone);
        }
    }

    public function test_lead_without_phone_has_default_country_code_when_edited(): void
    {
        $lead = Lead::create(['received_at' => '2026-11-27', 'name' => 'Без телефона']);

        Livewire::test(ListLeads::class)
            ->mountTableAction('edit', $lead)
            ->assertSet('mountedActions.0.data.phone_country_code', '375')
            ->callMountedTableAction()->assertHasNoTableActionErrors();

        $this->assertNull($lead->fresh()->phone);
    }

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
