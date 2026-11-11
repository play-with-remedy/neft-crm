<?php

namespace Tests\Feature;

use App\Filament\Resources\Evenings\Pages\EditEvening;
use App\Models\Evening;
use App\Models\ExpenseCategory;
use App\Models\Host;
use App\Models\PaymentType;
use App\Models\Player;
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
            ->fillForm([
                'participants_batch_count' => 10,
                'participants_batch_paid_amount' => 32.50,
            ])
            ->callFormComponentAction('participants_batch_actions', 'add_participants_batch')
            ->assertCount('data.participants', 10)
            ->assertSet('data.participants', fn (array $participants): bool => collect($participants)
                ->every(function (array $participant) use ($paymentType): bool {
                    $payment = collect($participant['payments'])->first();

                    return $payment['payment_type_id'] === $paymentType->id
                        && (float) $payment['amount'] === 32.5;
                }));
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
                ->every(fn (array $participant): bool => collect($participant['payments'])
                    ->every(fn (array $payment): bool => (float) $payment['amount'] === 0.0)));
    }

    public function test_invalid_evening_date_is_rejected_without_losing_form_state(): void
    {
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);

        Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->fillForm([
                'played_at' => '3026-09-08',
                'participants_batch_count' => 17,
            ])
            ->call('save')
            ->assertHasFormErrors(['played_at' => 'before_or_equal'])
            ->assertSet('data.played_at', '3026-09-08')
            ->assertSet('data.participants_batch_count', 17);

        $this->assertSame('2026-09-08', $evening->fresh()->played_at->toDateString());
    }

    public function test_duplicate_expense_categories_are_rejected_before_database_save(): void
    {
        $category = ExpenseCategory::create(['name' => 'Аренда']);
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);

        Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->fillForm([
                'expenses' => [
                    ['expense_category_id' => $category->id, 'amount' => 100],
                    ['expense_category_id' => $category->id, 'amount' => 200],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors()
            ->assertSee('Эта статья расходов уже добавлена.');

        $this->assertCount(0, $evening->fresh()->expenses);
    }

    public function test_evening_monetary_fields_keep_two_decimal_places(): void
    {
        $paymentType = PaymentType::create(['type' => 'Cash']);
        $cardPaymentType = PaymentType::create(['type' => 'Card']);
        $expenseCategory = ExpenseCategory::create(['name' => 'Rent']);
        $host = Host::create(['nickname' => 'Host']);
        $player = Player::create(['nickname' => 'Player']);
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);

        Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->fillForm([
                'expenses' => [[
                    'expense_category_id' => $expenseCategory->id,
                    'amount' => 20.15,
                ]],
                'staff' => [[
                    'host_id' => $host->id,
                    'role' => 'host',
                    'salary' => 32.50,
                ]],
                'participants' => [[
                    'player_id' => $player->id,
                    'payments' => [[
                        'payment_type_id' => $paymentType->id,
                        'amount' => 10,
                    ], [
                        'payment_type_id' => $cardPaymentType->id,
                        'amount' => 30,
                    ]],
                    'is_new_player' => false,
                    'note' => null,
                ]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $evening->refresh();

        $this->assertSame('20.15', $evening->expenses->sole()->amount);
        $this->assertSame('32.50', $evening->staff->sole()->salary);
        $participant = $evening->participants->sole();

        $this->assertSame('40.00', $participant->paid_amount);
        $this->assertSame(['10.00', '30.00'], $participant->payments->pluck('amount')->all());
    }

    public function test_same_payment_type_cannot_be_added_twice_for_one_participant(): void
    {
        $paymentType = PaymentType::create(['type' => 'Cash']);
        $player = Player::create(['nickname' => 'Player']);
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);

        Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->fillForm([
                'participants' => [[
                    'player_id' => $player->id,
                    'payments' => [[
                        'payment_type_id' => $paymentType->id,
                        'amount' => 10,
                    ], [
                        'payment_type_id' => $paymentType->id,
                        'amount' => 20,
                    ]],
                    'is_new_player' => false,
                ]],
            ])
            ->call('save')
            ->assertHasFormErrors();

        $this->assertCount(0, $evening->fresh()->participants);
    }

    public function test_payment_row_actions_add_rows_and_disable_deleting_the_last_one(): void
    {
        $cash = PaymentType::create(['type' => 'Cash']);
        $card = PaymentType::create(['type' => 'Card']);
        $player = Player::create(['nickname' => 'Player']);
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);

        $component = Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->fillForm([
                'participants' => [[
                    'player_id' => $player->id,
                    'payments' => [[
                        'payment_type_id' => $cash->id,
                        'amount' => 10,
                    ]],
                    'is_new_player' => false,
                ]],
            ]);

        $participantKey = array_key_first($component->get('data.participants'));
        $paymentsPath = "participants.{$participantKey}.payments";
        $paymentKey = array_key_first($component->get("data.{$paymentsPath}"));

        $component
            ->assertFormComponentActionVisible($paymentsPath, 'add_payment', ['item' => $paymentKey])
            ->assertFormComponentActionVisible($paymentsPath, 'delete', ['item' => $paymentKey])
            ->assertFormComponentActionDisabled($paymentsPath, 'delete', ['item' => $paymentKey])
            ->callFormComponentAction($paymentsPath, 'add_payment', arguments: ['item' => $paymentKey])
            ->assertCount("data.{$paymentsPath}", 2)
            ->assertSet("data.{$paymentsPath}", fn (array $payments): bool => collect($payments)
                ->contains(fn (array $payment): bool => (int) $payment['payment_type_id'] === $card->id));

        $paymentKey = array_key_first($component->get("data.{$paymentsPath}"));

        $component
            ->assertFormComponentActionVisible($paymentsPath, 'delete', ['item' => $paymentKey])
            ->assertFormComponentActionEnabled($paymentsPath, 'delete', ['item' => $paymentKey])
            ->callFormComponentAction($paymentsPath, 'delete', arguments: ['item' => $paymentKey])
            ->assertCount("data.{$paymentsPath}", 1);

        $remainingPaymentKey = array_key_first($component->get("data.{$paymentsPath}"));

        $component->assertFormComponentActionDisabled(
            $paymentsPath,
            'delete',
            ['item' => $remainingPaymentKey],
        );
    }
}
