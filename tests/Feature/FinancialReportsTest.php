<?php

namespace Tests\Feature;

use App\Filament\Pages\CashBook;
use App\Filament\Pages\PlayerFunnel;
use App\Filament\Pages\PlayerRating;
use App\Filament\Pages\StaffSalaries;
use App\Models\Evening;
use App\Models\EveningType;
use App\Models\ExpenseCategory;
use App\Models\Host;
use App\Models\PaymentType;
use App\Models\Player;
use App\Models\Project;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PDO;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Для тестов финансовых отчётов требуется PDO SQLite.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_cash_book_calculates_totals_for_selected_period(): void
    {
        $cash = PaymentType::create(['type' => 'Наличные']);
        $card = PaymentType::create(['type' => 'Карта']);
        $host = Host::create(['nickname' => 'Ведущий']);
        $expenseCategory = ExpenseCategory::create(['name' => 'Аренда']);
        $januaryPlayer = $this->createPlayer('Январский игрок');
        $februaryPlayer = $this->createPlayer('Февральский игрок');
        $secondFebruaryPlayer = $this->createPlayer('Второй февральский игрок');

        $january = Evening::create([
            'played_at' => '2026-01-31 20:00:00',
            'other_expenses' => 5,
        ]);
        $january->participants()->create([
            'player_id' => $januaryPlayer->id,
            'payment_type_id' => $cash->id,
            'paid_amount' => 100,
        ]);
        $january->staff()->create([
            'host_id' => $host->id,
            'role' => 'host',
            'salary' => 20,
        ]);
        $january->expenses()->create([
            'expense_category_id' => $expenseCategory->id,
            'amount' => 10,
        ]);

        $february = Evening::create([
            'played_at' => '2026-02-01 00:00:00',
            'other_expenses' => 10,
        ]);
        $february->participants()->createMany([
            [
                'player_id' => $februaryPlayer->id,
                'payment_type_id' => $cash->id,
                'paid_amount' => 200,
            ],
            [
                'player_id' => $secondFebruaryPlayer->id,
                'payment_type_id' => $card->id,
                'paid_amount' => 50,
            ],
        ]);
        $february->staff()->create([
            'host_id' => $host->id,
            'role' => 'host',
            'salary' => 40,
        ]);
        $february->expenses()->create([
            'expense_category_id' => $expenseCategory->id,
            'amount' => 30,
        ]);

        $component = Livewire::test(CashBook::class)
            ->filterTable('played_at', [
                'from' => '2026-02-01',
                'until' => '2026-02-28',
            ])
            ->assertCountTableRecords(1)
            ->assertCanSeeTableRecords([$february])
            ->assertCanNotSeeTableRecords([$january]);

        $totals = $component->instance()->getTotals();
        $paymentTotals = collect($totals['payment_types'])->keyBy('label');

        $this->assertSame(250, $totals['revenue']);
        $this->assertSame(40, $totals['expenses']);
        $this->assertSame(40, $totals['staff_salary']);
        $this->assertSame(170, $totals['profit']);
        $this->assertSame(200, $paymentTotals['Наличные']['value']);
        $this->assertSame(50, $paymentTotals['Карта']['value']);
    }

    public function test_staff_salary_month_filter_limits_sums_and_unique_evenings(): void
    {
        $host = Host::create(['nickname' => 'Сотрудник']);
        $january = Evening::create(['played_at' => '2026-01-20 19:00:00']);
        $februaryFirst = Evening::create(['played_at' => '2026-02-10 19:00:00']);
        $februarySecond = Evening::create(['played_at' => '2026-02-20 19:00:00']);

        $january->staff()->create([
            'host_id' => $host->id,
            'role' => 'host',
            'salary' => 50,
        ]);
        $februaryFirst->staff()->createMany([
            ['host_id' => $host->id, 'role' => 'host', 'salary' => 70],
            ['host_id' => $host->id, 'role' => 'manager', 'salary' => 30],
        ]);
        $februarySecond->staff()->create([
            'host_id' => $host->id,
            'role' => 'host',
            'salary' => 80,
        ]);

        $component = Livewire::test(StaffSalaries::class)
            ->filterTable('month', '2026-02')
            ->assertCountTableRecords(1);

        $record = $component->instance()
            ->getFilteredTableQuery()
            ->findOrFail($host->id);

        $this->assertSame(150, (int) $record->host_salary);
        $this->assertSame(2, (int) $record->host_evenings_count);
        $this->assertSame(30, (int) $record->manager_salary);
        $this->assertSame(1, (int) $record->manager_evenings_count);
        $this->assertSame(180, (int) $record->total_salary);
        $this->assertSame(2, (int) $record->total_evenings_count);
    }

    public function test_player_rating_combines_month_project_and_type_filters(): void
    {
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        $projectA = Project::create(['name' => 'Проект A']);
        $projectB = Project::create(['name' => 'Проект B']);
        $typeA = EveningType::create(['name' => 'Тип A']);
        $typeB = EveningType::create(['name' => 'Тип B']);
        $matchingPlayer = $this->createPlayer('Подходящий');
        $otherProjectPlayer = $this->createPlayer('Другой проект');
        $otherTypePlayer = $this->createPlayer('Другой тип');

        $matchingEvening = Evening::create([
            'played_at' => '2026-02-15 19:00:00',
            'project_id' => $projectA->id,
            'evening_type_id' => $typeA->id,
        ]);
        $otherProjectEvening = Evening::create([
            'played_at' => '2026-02-16 19:00:00',
            'project_id' => $projectB->id,
            'evening_type_id' => $typeA->id,
        ]);
        $otherTypeEvening = Evening::create([
            'played_at' => '2026-02-17 19:00:00',
            'project_id' => $projectA->id,
            'evening_type_id' => $typeB->id,
        ]);

        $matchingEvening->participants()->create([
            'player_id' => $matchingPlayer->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 120,
        ]);
        $otherProjectEvening->participants()->create([
            'player_id' => $otherProjectPlayer->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 200,
        ]);
        $otherTypeEvening->participants()->create([
            'player_id' => $otherTypePlayer->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 300,
        ]);

        $component = Livewire::test(PlayerRating::class)
            ->filterTable('month', '2026-02')
            ->filterTable('project', $projectA->id)
            ->filterTable('evening_type', $typeA->id)
            ->assertCountTableRecords(1)
            ->assertCanSeeTableRecords([$matchingPlayer])
            ->assertCanNotSeeTableRecords([$otherProjectPlayer, $otherTypePlayer]);

        $record = $component->instance()
            ->getFilteredTableQuery()
            ->findOrFail($matchingPlayer->id);

        $this->assertSame(1, (int) $record->participations_count);
        $this->assertSame(120, (int) $record->participations_sum_paid_amount);
        $this->assertSame('2026-02-15', substr((string) $record->statistics_first_visit, 0, 10));
        $this->assertSame('2026-02-15', substr((string) $record->statistics_last_visit, 0, 10));
    }

    public function test_player_funnel_calculates_stage_statistics_by_first_visit_month(): void
    {
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        $januaryPlayer = Player::create([
            'nickname' => 'Январский новичок',
            'first_visit_at' => '2026-01-31',
        ]);
        $februaryPlayer = Player::create([
            'nickname' => 'Февральский новичок',
            'first_visit_at' => '2026-02-01',
        ]);
        Player::create([
            'nickname' => 'Февральский новичок без связанного вечера',
            'first_visit_at' => '2026-02-20',
        ]);
        $withoutFirstVisit = Player::create([
            'nickname' => 'Без даты',
            'first_visit_at' => null,
        ]);
        $firstEvening = Evening::create(['played_at' => '2026-02-01 19:00:00']);
        $secondEvening = Evening::create(['played_at' => '2026-04-10 19:00:00']);

        $firstEvening->participants()->create([
            'player_id' => $februaryPlayer->id,
            'payment_type_id' => $paymentType->id,
        ]);
        $secondEvening->participants()->create([
            'player_id' => $februaryPlayer->id,
            'payment_type_id' => $paymentType->id,
        ]);

        $component = Livewire::withQueryParams(['month' => '2026-02'])
            ->test(PlayerFunnel::class)
            ->assertSet('period', '2026-02');

        $stats = $component->instance()->getFunnelStats();
        $timings = collect($component->instance()->getTransitionTimingStats())->keyBy('key');
        $stages = collect($stats['stages'])->keyBy('key');
        $losses = collect($stats['losses'])->keyBy('label');
        $dynamics = collect($component->get('playerDynamics')['rows'])->keyBy('month');

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stages['returned']['count']);
        $this->assertSame(2, $stages['new']['count']);
        $this->assertSame(100.0, $stages['new']['percentage']);
        $this->assertSame(50.0, $stages['returned']['percentage']);
        $this->assertSame(0, $stages['interested']['count']);
        $this->assertSame(1, $losses['После 1-го визита']['count']);
        $this->assertSame(50.0, $losses['После 1-го визита']['percentage']);
        $this->assertSame(1, $losses['После 2-го визита']['count']);
        $this->assertSame(100.0, $losses['После 2-го визита']['percentage']);
        $this->assertSame(68.0, $timings['new_returned']['average_days']);
        $this->assertSame(68.0, $timings['new_returned']['median_days']);
        $this->assertSame(1, $dynamics['2026-01']['counts']['new']);
        $this->assertSame(2, $dynamics['2026-02']['counts']['new']);
        $this->assertSame(1, $dynamics['2026-02']['counts']['returned']);
    }

    private function createPlayer(string $nickname): Player
    {
        return Player::create([
            'nickname' => $nickname,
            'first_name' => null,
            'gender' => null,
            'birth_day' => null,
            'birth_month' => null,
        ]);
    }
}
