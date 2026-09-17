<?php

namespace Tests\Feature;

use App\Filament\Pages\CashBook;
use App\Filament\Pages\LtvAnalysis;
use App\Filament\Pages\MonthlyFinances;
use App\Filament\Pages\PlayerAnalytics;
use App\Filament\Pages\PlayerFunnel;
use App\Filament\Pages\StaffSalaries;
use App\Livewire\StaffSalaryEvenings;
use App\Models\Evening;
use App\Models\EveningType;
use App\Models\ExpenseCategory;
use App\Models\FinancialCategory;
use App\Models\FinancialCategoryValue;
use App\Models\FinancialPeriodValue;
use App\Models\Host;
use App\Models\PaymentType;
use App\Models\Player;
use App\Models\Project;
use App\Support\PlayerAnalyticsXlsxExporter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
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

    public function test_monthly_finances_calculates_rows_and_revenue_change(): void
    {
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        $host = Host::create(['nickname' => 'Ведущий']);
        $expenseCategory = ExpenseCategory::create(['name' => 'Аренда']);
        $player = $this->createPlayer('Игрок финансовой таблицы');
        $manualExpenseCategory = FinancialCategory::create([
            'name' => 'Реклама',
            'sort_order' => 1,
        ]);
        FinancialPeriodValue::create([
            'period' => '2026-01-01',
            'corporate_revenue' => 50,
        ]);
        FinancialPeriodValue::create([
            'period' => '2026-02-01',
            'corporate_revenue' => 50,
        ]);
        FinancialCategoryValue::create([
            'financial_category_id' => $manualExpenseCategory->id,
            'period' => '2026-01-01',
            'amount' => 40,
        ]);
        FinancialCategoryValue::create([
            'financial_category_id' => $manualExpenseCategory->id,
            'period' => '2026-02-01',
            'amount' => 25,
        ]);

        $january = Evening::create([
            'played_at' => '2026-01-15 19:00:00',
            'other_expenses' => 5,
        ]);
        $january->participants()->create([
            'player_id' => $player->id,
            'payment_type_id' => $paymentType->id,
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
            'played_at' => '2026-02-15 19:00:00',
            'other_expenses' => 10,
        ]);
        $february->participants()->create([
            'player_id' => $player->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 150,
        ]);
        $february->staff()->create([
            'host_id' => $host->id,
            'role' => 'host',
            'salary' => 30,
        ]);
        $february->expenses()->create([
            'expense_category_id' => $expenseCategory->id,
            'amount' => 20,
        ]);

        $component = Livewire::withQueryParams([
            'from' => '2026-01',
            'until' => '2026-02',
        ])->test(MonthlyFinances::class);

        $rows = collect($component->get('rows'))->keyBy('month');

        $this->assertSame(['2026-02', '2026-01'], collect($component->get('rows'))->pluck('month')->all());
        $this->assertSame('Январь 2026', $rows['2026-01']['label']);
        $this->assertSame(150.0, $rows['2026-01']['revenue']);
        $this->assertSame(75.0, $rows['2026-01']['expenses']);
        $this->assertSame(75.0, $rows['2026-01']['profit']);
        $this->assertSame(50.0, $rows['2026-01']['margin']);
        $this->assertNull($rows['2026-01']['revenue_change']);
        $this->assertSame(200.0, $rows['2026-02']['revenue']);
        $this->assertSame(85.0, $rows['2026-02']['expenses']);
        $this->assertSame(115.0, $rows['2026-02']['profit']);
        $this->assertSame(57.5, $rows['2026-02']['margin']);
        $this->assertSame(33.3, $rows['2026-02']['revenue_change']);

        $component
            ->set('comparisonMonthA', '2026-01')
            ->set('comparisonMonthB', '2026-02')
            ->call('applyComparison');

        $comparisonSummary = collect($component->get('comparisonSummaryRows'))->keyBy('label');
        $comparisonCategories = collect($component->get('comparisonCategoryRows'))->keyBy('id');

        $this->assertSame(150.0, $comparisonSummary['Общая выручка']['value_a']);
        $this->assertSame(200.0, $comparisonSummary['Общая выручка']['value_b']);
        $this->assertSame(50.0, $comparisonSummary['Общая выручка']['difference']);
        $this->assertSame(33.3, $comparisonSummary['Общая выручка']['change']);
        $this->assertSame(75.0, $comparisonSummary['Общие расходы']['value_a']);
        $this->assertSame(85.0, $comparisonSummary['Общие расходы']['value_b']);
        $this->assertSame(75.0, $comparisonSummary['Чистая прибыль']['value_a']);
        $this->assertSame(115.0, $comparisonSummary['Чистая прибыль']['value_b']);
        $this->assertSame(7.5, $comparisonSummary['Маржа']['difference']);
        $this->assertSame(40.0, $comparisonCategories[$manualExpenseCategory->id]['value_a']);
        $this->assertSame(25.0, $comparisonCategories[$manualExpenseCategory->id]['value_b']);
        $this->assertSame(-15.0, $comparisonCategories[$manualExpenseCategory->id]['difference']);
        $this->assertSame(-37.5, $comparisonCategories[$manualExpenseCategory->id]['change']);

        $component
            ->call('sortTable', 'revenue')
            ->assertSet('sortColumn', 'revenue')
            ->assertSet('sortDirection', 'desc')
            ->call('sortTable', 'revenue')
            ->assertSet('sortDirection', 'asc');

        $this->assertSame(
            ['2026-01', '2026-02'],
            collect($component->get('rows'))->pluck('month')->all(),
        );
    }

    public function test_monthly_finances_accepts_from_query_parameter_without_until(): void
    {
        Evening::create(['played_at' => '2026-02-15 19:00:00']);

        Livewire::withQueryParams(['from' => '2026-01'])
            ->test(MonthlyFinances::class)
            ->assertSet('periodFrom', '2026-01')
            ->assertSet('periodUntil', '2026-02');
    }

    public function test_monthly_finances_defaults_to_current_year(): void
    {
        $this->travelTo('2026-08-30 12:00:00');

        Livewire::test(MonthlyFinances::class)
            ->assertSet('periodFrom', '2026-01')
            ->assertSet('periodUntil', '2026-08');
    }

    public function test_staff_salary_date_range_filter_limits_sums_and_unique_evenings(): void
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
            ->filterTable('played_at', ['from' => '2026-02-01', 'until' => '2026-02-20'])
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

        $component
            ->mountTableAction('view_host_salary_evenings', $host->getKey());

        $modal = $component->getMountedActionModalHtml();

        $this->assertStringContainsString('10.02.2026', $modal);
        $this->assertStringContainsString('20.02.2026', $modal);
        $this->assertStringNotContainsString('20.01.2026', $modal);
        $this->assertStringContainsString('150 BYN', $modal);
    }

    public function test_staff_salary_evenings_modal_paginates_by_five_evenings(): void
    {
        $host = Host::create(['nickname' => 'Сотрудник с историей']);

        foreach (range(1, 11) as $day) {
            Evening::create(['played_at' => "2026-03-{$day} 19:00:00"])
                ->staff()
                ->create([
                    'host_id' => $host->id,
                    'role' => 'host',
                    'salary' => 50,
                ]);
        }

        Livewire::test(StaffSalaryEvenings::class, [
            'hostId' => $host->id,
            'role' => 'host',
            'periodLabel' => 'За всё время',
        ])
            ->assertViewHas('evenings', fn ($evenings): bool => $evenings->count() === 5
                && $evenings->perPage() === 5
                && $evenings->currentPage() === 1)
            ->call('nextPage', 'staffEveningsPage')
            ->assertViewHas('evenings', fn ($evenings): bool => $evenings->count() === 5
                && $evenings->currentPage() === 2)
            ->call('nextPage', 'staffEveningsPage')
            ->assertViewHas('evenings', fn ($evenings): bool => $evenings->count() === 1
                && $evenings->currentPage() === 3);
    }

    public function test_staff_salary_filters_limit_totals_and_details_by_project_type_and_project(): void
    {
        $host = Host::create(['nickname' => 'Filtered employee']);
        $wantedType = EveningType::create(['name' => 'Wanted type']);
        $otherType = EveningType::create(['name' => 'Other type']);
        $wantedProject = Project::create(['name' => 'Wanted project']);
        $otherProject = Project::create(['name' => 'Other project']);

        $matching = Evening::create([
            'played_at' => '2026-04-10 19:00:00',
            'evening_type_id' => $wantedType->id,
            'project_id' => $wantedProject->id,
        ]);
        $wrongProject = Evening::create([
            'played_at' => '2026-04-11 19:00:00',
            'evening_type_id' => $wantedType->id,
            'project_id' => $otherProject->id,
        ]);
        $wrongType = Evening::create([
            'played_at' => '2026-04-12 19:00:00',
            'evening_type_id' => $otherType->id,
            'project_id' => $wantedProject->id,
        ]);

        $matching->staff()->create(['host_id' => $host->id, 'role' => 'host', 'salary' => 100]);
        $wrongProject->staff()->create(['host_id' => $host->id, 'role' => 'host', 'salary' => 200]);
        $wrongType->staff()->create(['host_id' => $host->id, 'role' => 'host', 'salary' => 300]);

        $component = Livewire::test(StaffSalaries::class)
            ->filterTable('evening_type_id', $wantedType->id)
            ->filterTable('project_id', $wantedProject->id);

        $record = $component->instance()->getFilteredTableQuery()->findOrFail($host->id);

        $this->assertSame(100, (int) $record->total_salary);
        $this->assertSame(1, (int) $record->total_evenings_count);

        Livewire::test(StaffSalaryEvenings::class, [
            'hostId' => $host->id,
            'periodLabel' => 'All time',
            'eveningTypeId' => $wantedType->id,
            'projectId' => $wantedProject->id,
        ])
            ->assertViewHas('evenings', fn ($evenings): bool => $evenings->count() === 1
                && $evenings->first()->is($matching))
            ->assertViewHas('totalSalary', 100)
            ->assertViewHas('eveningsCount', 1);
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

        $component = Livewire::withQueryParams(['months' => ['2026-02']])
            ->test(PlayerFunnel::class)
            ->assertSet('periods', ['2026-02']);

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

    public function test_player_analytics_computed_columns_can_be_sorted(): void
    {
        Player::create([
            'nickname' => 'Игрок сезона',
            'manual_activity_status' => Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
        ]);
        Player::create(['nickname' => 'Гость клуба']);

        Livewire::test(PlayerAnalytics::class)
            ->sortTable('status')
            ->assertCountTableRecords(2)
            ->sortTable('activity_status')
            ->assertCountTableRecords(2)
            ->sortTable('ltv_total')
            ->assertCountTableRecords(2)
            ->assertDontSee('Средний чек');
    }

    public function test_player_analytics_can_filter_by_both_status_types(): void
    {
        $seasonPlayer = Player::create([
            'nickname' => 'Игрок сезона',
            'manual_activity_status' => Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
        ]);
        $clubGuest = Player::create(['nickname' => 'Гость клуба']);

        Livewire::test(PlayerAnalytics::class)
            ->filterTable('funnel_status', 'none')
            ->assertCountTableRecords(2);

        Livewire::test(PlayerAnalytics::class)
            ->filterTable('activity_status', 'season_player')
            ->assertCountTableRecords(1)
            ->assertCanSeeTableRecords([$seasonPlayer])
            ->assertCanNotSeeTableRecords([$clubGuest]);

        Livewire::test(PlayerAnalytics::class)
            ->filterTable('activity_status', 'club_guest')
            ->assertCountTableRecords(1)
            ->assertCanSeeTableRecords([$clubGuest])
            ->assertCanNotSeeTableRecords([$seasonPlayer]);
    }

    public function test_player_analytics_can_filter_by_visit_period_type_and_project(): void
    {
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        $wantedType = EveningType::create(['name' => 'Квиз']);
        $otherType = EveningType::create(['name' => 'Мафия']);
        $wantedProject = Project::create(['name' => 'Основной проект']);
        $otherProject = Project::create(['name' => 'Другой проект']);
        $wantedPlayer = Player::create(['nickname' => 'Подходящий игрок']);
        $otherPlayer = Player::create(['nickname' => 'Другой игрок']);

        $wantedEvening = Evening::create([
            'played_at' => '2026-05-15 19:00:00',
            'evening_type_id' => $wantedType->id,
            'project_id' => $wantedProject->id,
        ]);
        $secondWantedEvening = Evening::create([
            'played_at' => '2026-05-25 19:00:00',
            'evening_type_id' => $wantedType->id,
            'project_id' => $wantedProject->id,
        ]);
        $otherEvening = Evening::create([
            'played_at' => '2026-06-15 19:00:00',
            'evening_type_id' => $otherType->id,
            'project_id' => $otherProject->id,
        ]);
        $wantedEvening->participants()->create(['player_id' => $wantedPlayer->id, 'payment_type_id' => $paymentType->id, 'paid_amount' => 20]);
        $secondWantedEvening->participants()->create(['player_id' => $wantedPlayer->id, 'payment_type_id' => $paymentType->id, 'paid_amount' => 30]);
        $otherEvening->participants()->create(['player_id' => $otherPlayer->id, 'payment_type_id' => $paymentType->id, 'paid_amount' => 20]);

        for ($visit = 1; $visit <= 19; $visit++) {
            $outsideScopeEvening = Evening::create([
                'played_at' => "2026-06-{$visit} 19:00:00",
                'evening_type_id' => $otherType->id,
                'project_id' => $otherProject->id,
            ]);
            $outsideScopeEvening->participants()->create([
                'player_id' => $wantedPlayer->id,
                'payment_type_id' => $paymentType->id,
                'paid_amount' => 100,
            ]);
        }

        $component = Livewire::test(PlayerAnalytics::class)
            ->filterTable('played_at', ['from' => '2026-05-01', 'until' => '2026-05-31'])
            ->filterTable('evening_type_id', $wantedType->id)
            ->filterTable('project_id', $wantedProject->id)
            ->assertCountTableRecords(1)
            ->assertCanSeeTableRecords([$wantedPlayer])
            ->assertCanNotSeeTableRecords([$otherPlayer]);

        $record = $component->instance()->getFilteredTableQuery()->findOrFail($wantedPlayer->id);

        $this->assertSame(2, (int) $record->visits_count);
        $this->assertSame(21, (int) $record->lifetime_visits_count);
        $this->assertSame(21, (int) $record->recent_visits_count);
        $this->assertSame(50, (int) $record->ltv_total);
        $this->assertSame('2026-05-15', $record->first_visit_at->format('Y-m-d'));
        $this->assertSame('2026-05-25', Carbon::parse($record->last_visit_at)->format('Y-m-d'));
        $this->assertSame('2026-05-15', Carbon::parse($record->lifetime_first_visit_at)->format('Y-m-d'));
        $this->assertSame('2026-06-19', Carbon::parse($record->lifetime_last_visit_at)->format('Y-m-d'));

        $table = $component->instance()->getTable();

        $this->assertSame('regular', $table->getColumn('status')->record($record)->getState());
        $this->assertSame('Клубный игрок', $table->getColumn('activity_status')->record($record)->getState());
        $this->assertSame('1 месяц 4 дня', $table->getColumn('duration')->record($record)->getState());
    }

    public function test_player_analytics_exports_filtered_top_players_to_xlsx(): void
    {
        $paymentType = PaymentType::create(['type' => 'Cash']);
        $wantedType = EveningType::create(['name' => 'Quiz']);
        $otherType = EveningType::create(['name' => 'Mafia']);
        $wantedProject = Project::create(['name' => 'Main project']);
        $topPlayer = Player::create(['nickname' => 'Top player']);
        $secondPlayer = Player::create(['nickname' => 'Second player']);
        $excludedPlayer = Player::create(['nickname' => 'Excluded player']);

        $wantedEvening = Evening::create([
            'played_at' => '2026-05-15 19:00:00',
            'evening_type_id' => $wantedType->id,
            'project_id' => $wantedProject->id,
        ]);
        $otherEvening = Evening::create([
            'played_at' => '2026-05-16 19:00:00',
            'evening_type_id' => $otherType->id,
            'project_id' => $wantedProject->id,
        ]);

        $wantedEvening->participants()->createMany([
            ['player_id' => $topPlayer->id, 'payment_type_id' => $paymentType->id, 'paid_amount' => 150],
            ['player_id' => $secondPlayer->id, 'payment_type_id' => $paymentType->id, 'paid_amount' => 100],
        ]);
        $otherEvening->participants()->create([
            'player_id' => $excludedPlayer->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 500,
        ]);

        $component = Livewire::test(PlayerAnalytics::class)
            ->filterTable('played_at', ['from' => '2026-05-01', 'until' => '2026-05-31'])
            ->filterTable('evening_type_id', $wantedType->id)
            ->filterTable('project_id', $wantedProject->id)
            ->assertActionExists('exportTopPlayers');

        $players = $component->instance()->topPlayersForExport(1);
        $filters = $component->instance()->appliedFiltersLabel();

        $this->assertCount(1, $players);
        $this->assertTrue($players->first()->is($topPlayer));
        $this->assertSame(150, (int) $players->first()->ltv_total);
        $this->assertStringContainsString('01.05.2026', $filters);
        $this->assertStringContainsString('Quiz', $filters);
        $this->assertStringContainsString('Main project', $filters);

        $component
            ->callAction('exportTopPlayers', ['limit' => 1])
            ->assertFileDownloaded();

        $path = tempnam(sys_get_temp_dir(), 'player-analytics-');

        try {
            PlayerAnalyticsXlsxExporter::write($players, $filters, $path);

            $reader = new Reader;
            $reader->open($path);
            $rows = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
                }

                break;
            }

            $reader->close();

            $this->assertSame($filters, $rows[0][0]);
            $this->assertSame(['Ник', 'LTV всего'], $rows[1]);
            $this->assertSame('Top player', $rows[2][0]);
            $this->assertSame(150, $rows[2][1]);
        } finally {
            @unlink($path);
        }
    }

    public function test_ltv_analysis_calculates_metrics_for_first_visit_cohort(): void
    {
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        $firstPlayer = Player::create([
            'nickname' => 'Первый новичок',
            'first_visit_at' => '2026-01-05',
        ]);
        $secondPlayer = Player::create([
            'nickname' => 'Второй новичок',
            'first_visit_at' => '2026-01-10',
        ]);
        $firstEvening = Evening::create(['played_at' => '2026-01-05 19:00:00']);
        $secondEvening = Evening::create(['played_at' => '2026-01-10 19:00:00']);
        $laterEvening = Evening::create(['played_at' => '2026-02-04 19:00:00']);

        $firstEvening->participants()->create([
            'player_id' => $firstPlayer->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 100,
        ]);
        $laterEvening->participants()->create([
            'player_id' => $firstPlayer->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 200,
        ]);
        $secondEvening->participants()->create([
            'player_id' => $secondPlayer->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 50,
        ]);

        $component = Livewire::withQueryParams([
            'from' => '2026-01',
            'until' => '2026-01',
        ])->test(LtvAnalysis::class);

        $this->assertSame(2, $component->get('summary')['new_players_count']);
        $this->assertSame(350.0, $component->get('summary')['revenue']);
        $this->assertSame(175.0, $component->get('summary')['average_ltv']);
        $this->assertSame(1.5, $component->get('summary')['average_visits']);
        $this->assertSame(15.0, $component->get('summary')['average_lifetime_days']);
        $this->assertSame('2026-01', $component->get('rows')[0]['month']);

        foreach ([
            'new_players_count',
            'revenue',
            'average_ltv',
            'average_visits',
            'average_lifetime_days',
        ] as $column) {
            $component
                ->call('sortTable', $column)
                ->assertSet('sortColumn', $column);
        }
    }

    public function test_ltv_analysis_paginates_month_rows_on_the_server(): void
    {
        $component = Livewire::withQueryParams([
            'from' => '2026-01',
            'until' => '2026-08',
        ])->test(LtvAnalysis::class);

        $this->assertCount(6, $component->instance()->getVisibleRows());
        $this->assertSame(2, $component->instance()->getTablePages());

        $component->call('nextTablePage')->assertSet('tablePage', 2);

        $this->assertCount(2, $component->instance()->getVisibleRows());

        $component->set('tablePerPage', '3')->assertSet('tablePage', 1);

        $this->assertCount(3, $component->instance()->getVisibleRows());
        $this->assertSame(3, $component->instance()->getTablePages());
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
