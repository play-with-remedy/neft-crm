<?php

namespace Tests\Feature;

use App\Filament\Resources\Evenings\Pages\EditEvening;
use App\Models\Evening;
use App\Models\PaymentType;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PDO;
use Tests\TestCase;

class EveningFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Для теста формы вечера требуется PDO SQLite.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_batch_rows_can_be_added_when_editing_an_empty_evening(): void
    {
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);

        Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->assertSet('data.participants', [])
            ->fillForm(['participants_batch_count' => 10])
            ->callFormComponentAction('participants_batch_actions', 'add_participants_batch')
            ->assertCount('data.participants', 10)
            ->assertSet('data.participants', fn (array $participants): bool => collect($participants)
                ->every(fn (array $participant): bool => $participant['payment_type_id'] === $paymentType->id));
    }

    public function test_free_batch_payment_resets_and_disables_paid_amount(): void
    {
        PaymentType::create(['type' => 'Наличные']);
        $freePaymentType = PaymentType::create(['type' => 'Бесплатно']);
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);

        Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->fillForm(['participants_batch_paid_amount' => 50])
            ->fillForm(['participants_batch_payment_type_id' => $freePaymentType->id])
            ->assertSet('data.participants_batch_paid_amount', 0)
            ->assertFormFieldDisabled('participants_batch_paid_amount')
            ->callFormComponentAction('participants_batch_actions', 'add_participants_batch')
            ->assertSet('data.participants', fn (array $participants): bool => collect($participants)
                ->every(fn (array $participant): bool => (float) $participant['paid_amount'] === 0.0));
    }
}
