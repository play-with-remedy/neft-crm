<?php

namespace Tests\Feature;

use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Models\Certificate;
use App\Models\Host;
use App\Models\Player;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_can_be_stored_without_usage_dates(): void
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
        ]);
        $this->assertSame(today()->format('d.m.Y'), $certificate->purchased_at->format('d.m.Y'));
        $this->assertCount(0, $certificate->usages);
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
        $host = Host::query()->create(['nickname' => 'Ведущий']);

        Livewire::test(ListCertificates::class)
            ->callAction('create', data: [
                'purchased_at' => today()->subDay()->toDateString(),
                'type' => Certificate::TYPE_CERTIFICATE,
                'player_id' => $player->id,
                'seller_host_id' => $host->id,
                'expires_at' => today()->addMonth()->toDateString(),
                'usages' => [],
            ])
            ->assertHasNoFormErrors();

        $certificate = Certificate::query()->sole();

        Livewire::test(ListCertificates::class)
            ->callTableAction('edit', $certificate, data: [
                'purchased_at' => today()->subDay()->toDateString(),
                'type' => Certificate::TYPE_TWO_FOR_ONE,
                'player_id' => $player->id,
                'seller_host_id' => $host->id,
                'expires_at' => today()->addMonth()->toDateString(),
                'usages' => [
                    ['used_at' => today()->subDay()->toDateString()],
                    ['used_at' => today()->toDateString()],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'type' => Certificate::TYPE_TWO_FOR_ONE,
            'player_id' => $player->id,
            'seller_host_id' => $host->id,
        ]);
        $this->assertSame($host->id, $certificate->fresh()->seller->id);
        $this->assertSame(today()->addMonth()->toDateString(), $certificate->fresh()->expires_at->toDateString());
        $this->assertSame([
            today()->subDay()->toDateString(),
            today()->toDateString(),
        ], $certificate->fresh()->usages->map(fn ($usage) => $usage->used_at->toDateString())->all());

        Livewire::test(ListCertificates::class)
            ->assertTableColumnStateSet('seller.nickname', 'Ведущий', $certificate)
            ->assertTableColumnStateSet('usages.used_at', $certificate->fresh()->usages->pluck('used_at')->all(), $certificate);

        $usage = $certificate->usages()->first();

        Livewire::test(ListCertificates::class)
            ->mountTableAction('edit', $certificate)
            ->set('mountedActions.0.data.usages', [
                "record-{$usage->id}" => ['used_at' => today()->toDateString()],
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('certificate_usages', 1);
        $this->assertSame(today()->toDateString(), $usage->fresh()->used_at->toDateString());

        Livewire::test(ListCertificates::class)
            ->callTableAction('delete', $certificate);

        $this->assertDatabaseMissing('certificates', ['id' => $certificate->id]);
        $this->assertDatabaseCount('certificate_usages', 0);
    }

    public function test_certificate_modal_rejects_invalid_dates(): void
    {
        Livewire::test(ListCertificates::class)
            ->callAction('create', data: $this->certificateData([
                'purchased_at' => today()->addDay()->toDateString(),
                'expires_at' => today()->toDateString(),
                'usages' => [
                    ['used_at' => today()->subDay()->toDateString()],
                ],
            ]))
            ->assertHasFormErrors([
                'purchased_at' => 'before_or_equal',
                'expires_at' => 'after_or_equal',
                'usages.0.used_at' => 'after_or_equal',
            ]);

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_seller_is_preloaded_and_usage_limit_is_not_shown(): void
    {
        $host = Host::query()->create(['nickname' => 'Ведущий']);

        Livewire::test(ListCertificates::class)
            ->mountAction('create')
            ->assertFormFieldExists('seller_host_id', fn (Select $field): bool => $field->isPreloaded()
                && $field->getOptions()[$host->id] === 'Ведущий')
            ->assertFormFieldDoesNotExist('usage_limit')
            ->assertTableColumnDoesNotExist('usages_count');
    }

    public function test_modal_rejects_usage_after_expiry_and_in_the_future(): void
    {
        $data = $this->certificateData([
            'expires_at' => today()->subDay()->toDateString(),
            'usages' => [['used_at' => today()->toDateString()]],
        ]);

        Livewire::test(ListCertificates::class)
            ->callAction('create', data: $data)
            ->assertHasFormErrors(['usages.0.used_at' => 'before_or_equal']);

        Livewire::test(ListCertificates::class)
            ->callAction('create', data: array_replace($data, [
                'expires_at' => today()->addMonth()->toDateString(),
                'usages' => [['used_at' => today()->addDay()->toDateString()]],
            ]))
            ->assertHasFormErrors(['usages.0.used_at' => 'before_or_equal']);

        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseCount('certificate_usages', 0);
    }

    public function test_modal_rejects_duplicate_dates(): void
    {
        $data = $this->certificateData([
            'usages' => [
                ['used_at' => today()->toDateString()],
                ['used_at' => today()->toDateString()],
            ],
        ]);

        Livewire::test(ListCertificates::class)
            ->callAction('create', data: $data)
            ->assertHasFormErrors(['usages.0.used_at', 'usages.1.used_at']);

        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseCount('certificate_usages', 0);
    }

    public function test_certificate_can_have_any_number_of_visits(): void
    {
        $dates = collect(range(1, 5))
            ->map(fn (int $daysAgo): array => ['used_at' => today()->subDays($daysAgo)->toDateString()])
            ->all();

        foreach ([Certificate::TYPE_CERTIFICATE, Certificate::TYPE_TWO_FOR_ONE] as $type) {
            Livewire::test(ListCertificates::class)
                ->callAction('create', data: $this->certificateData([
                    'type' => $type,
                    'purchased_at' => today()->subWeek()->toDateString(),
                    'usages' => $dates,
                ]))
                ->assertHasNoFormErrors();

            $certificate = Certificate::query()->latest('id')->firstOrFail();
            $this->assertCount(5, $certificate->usages);

            Livewire::test(ListCertificates::class)
                ->callTableAction('edit', $certificate, data: [
                    'usages' => [['used_at' => today()->toDateString()]],
                ])
                ->assertHasNoFormErrors();

            $this->assertCount(6, $certificate->fresh()->usages);
        }
    }

    public function test_modal_requires_a_seller_and_expiry(): void
    {
        Livewire::test(ListCertificates::class)
            ->callAction('create', data: $this->certificateData([
                'seller_host_id' => null,
                'expires_at' => null,
            ]))
            ->assertHasFormErrors([
                'seller_host_id' => 'required',
                'expires_at' => 'required',
            ]);

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_migrations_preserve_existing_usage_dates_without_a_usage_limit(): void
    {
        $removeLimit = require database_path('migrations/2026_11_04_130000_remove_usage_limit_from_certificates.php');
        $removeLimit->down();
        $migration = require database_path('migrations/2026_11_04_120000_extend_certificates_and_create_certificate_usages.php');
        $migration->down();

        $usedId = DB::table('certificates')->insertGetId([
            'purchased_at' => '2026-10-01',
            'type' => Certificate::TYPE_TWO_FOR_ONE,
            'purchaser_nickname' => 'Лис',
            'used_at' => '2026-10-05',
        ]);
        $unusedId = DB::table('certificates')->insertGetId([
            'purchased_at' => '2026-10-01',
            'type' => Certificate::TYPE_CERTIFICATE,
            'purchaser_nickname' => 'Сова',
        ]);

        $migration->up();

        $certificate = Certificate::query()->findOrFail($usedId);
        $this->assertSame('2026-10-05', $certificate->usages->sole()->used_at->toDateString());
        $this->assertNull($certificate->seller_host_id);
        $this->assertNull($certificate->expires_at);
        $this->assertCount(0, Certificate::query()->findOrFail($unusedId)->usages);
        $this->assertDatabaseCount('certificate_usages', 1);

        $migration->down();
        $this->assertDatabaseHas('certificates', ['id' => $usedId, 'used_at' => '2026-10-05']);
        $migration->up();
        $removeLimit->up();

        $this->assertFalse(Schema::hasColumn('certificates', 'usage_limit'));
        $this->assertSame('2026-10-05', $certificate->fresh()->usages->sole()->used_at->toDateString());
        $this->assertDatabaseCount('certificate_usages', 1);
    }

    private function certificateData(array $overrides = []): array
    {
        return array_replace([
            'purchased_at' => today()->subDays(3)->toDateString(),
            'type' => Certificate::TYPE_CERTIFICATE,
            'player_id' => Player::query()->create(['nickname' => 'Покупатель'])->id,
            'seller_host_id' => Host::query()->create(['nickname' => 'Продавец'])->id,
            'expires_at' => today()->addMonth()->toDateString(),
            'usages' => [],
        ], $overrides);
    }
}
