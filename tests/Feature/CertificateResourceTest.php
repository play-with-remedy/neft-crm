<?php

namespace Tests\Feature;

use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Models\Certificate;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_can_be_stored_with_an_optional_usage_date(): void
    {
        $player = Player::query()->create(['nickname' => 'Лис']);
        $certificate = Certificate::query()->create([
            'purchased_at' => today(),
            'type' => Certificate::TYPE_TWO_FOR_ONE,
            'player_id' => $player->id,
        ]);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'type' => Certificate::TYPE_TWO_FOR_ONE,
            'player_id' => $player->id,
            'purchaser_nickname' => 'Лис',
            'used_at' => null,
        ]);
        $this->assertSame(today()->format('d.m.Y'), $certificate->purchased_at->format('d.m.Y'));
        $this->assertNull($certificate->used_at);
    }

    public function test_certificate_type_options_are_fixed(): void
    {
        $this->assertSame([
            Certificate::TYPE_CERTIFICATE => 'Сертификат',
            Certificate::TYPE_TWO_FOR_ONE => '2 по цене одного',
        ], Certificate::typeOptions());
    }

    public function test_certificate_can_be_created_edited_and_deleted_in_modals(): void
    {
        $player = Player::query()->create(['nickname' => 'Сова']);

        Livewire::test(ListCertificates::class)
            ->callAction('create', data: [
                'purchased_at' => today()->subDay()->toDateString(),
                'type' => Certificate::TYPE_CERTIFICATE,
                'player_id' => $player->id,
                'used_at' => null,
            ])
            ->assertHasNoFormErrors();

        $certificate = Certificate::query()->sole();

        Livewire::test(ListCertificates::class)
            ->callTableAction('edit', $certificate, data: [
                'purchased_at' => today()->subDay()->toDateString(),
                'type' => Certificate::TYPE_TWO_FOR_ONE,
                'player_id' => $player->id,
                'used_at' => today()->toDateString(),
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'type' => Certificate::TYPE_TWO_FOR_ONE,
            'player_id' => $player->id,
            'used_at' => today()->startOfDay()->toDateTimeString(),
        ]);

        Livewire::test(ListCertificates::class)
            ->callTableAction('delete', $certificate);

        $this->assertDatabaseMissing('certificates', ['id' => $certificate->id]);
    }

    public function test_certificate_modal_rejects_invalid_dates(): void
    {
        $player = Player::query()->create(['nickname' => 'Ворон']);

        Livewire::test(ListCertificates::class)
            ->callAction('create', data: [
                'purchased_at' => today()->addDay()->toDateString(),
                'type' => Certificate::TYPE_CERTIFICATE,
                'player_id' => $player->id,
                'used_at' => today()->subDay()->toDateString(),
            ])
            ->assertHasFormErrors([
                'purchased_at' => 'before_or_equal',
                'used_at' => 'after_or_equal',
            ]);

        $this->assertDatabaseCount('certificates', 0);
    }
}
