<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\AutumnCampaignDashboard;
use App\Filament\Pages\CashBook;
use App\Filament\Pages\ImportPlayers;
use App\Filament\Resources\Evenings\Pages\ListEvenings;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Filament\Resources\Players\Pages\PlayerAutumnCase;
use App\Filament\Resources\Players\Pages\PlayerStatistics;
use App\Filament\Resources\Players\Pages\ViewPlayer;
use App\Filament\Resources\Players\PlayerResource;
use App\Filament\Resources\Sources\SourceResource;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\ClubWelcome;
use App\Filament\Widgets\ClubStatsOverview;
use App\Filament\Widgets\PlayerActivityChart;
use App\Filament\Widgets\PlayerStatsOverview;
use App\Filament\Widgets\PlayerVisitHistory;
use App\Models\Evening;
use App\Models\PaymentType;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_only_club_and_sales_sections(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->assertTrue(PlayerResource::canAccess());
        $this->assertTrue(LeadResource::canAccess());
        $this->assertTrue(AutumnCampaignDashboard::canAccess());

        $this->assertFalse(SourceResource::canAccess());
        $this->assertFalse(CashBook::canAccess());
        $this->assertFalse(ImportPlayers::canAccess());
        $this->assertFalse(UserResource::canAccess());
        $this->assertFalse(ClubStatsOverview::canView());
    }

    public function test_admin_receives_forbidden_response_for_restricted_direct_urls(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->get(SourceResource::getUrl(panel: 'admin'))
            ->assertForbidden()
            ->assertSee('Доступ ограничен')
            ->assertSee('На главную');
        $this->get(CashBook::getUrl(panel: 'admin'))->assertForbidden();
        $this->get(ImportPlayers::getUrl(panel: 'admin'))->assertForbidden();
        $this->get(UserResource::getUrl(panel: 'admin'))->assertForbidden();
    }

    public function test_admin_can_open_allowed_direct_urls(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->get(PlayerResource::getUrl(panel: 'admin'))->assertOk();
        $this->get(LeadResource::getUrl(panel: 'admin'))->assertOk();
        $this->get('/admin')
            ->assertOk()
            ->assertDontSee('Отчеты')
            ->assertDontSee('Служебные данные')
            ->assertDontSee('Кассовая книга')
            ->assertDontSee('Выручка')
            ->assertDontSee('Прибыль');
    }

    public function test_admin_cannot_use_player_or_evening_csv_actions(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(ListPlayers::class)
            ->assertActionHidden('import')
            ->assertActionHidden('export');

        $evening = Evening::query()->create(['played_at' => now()]);

        Livewire::test(ListEvenings::class)
            ->assertActionHidden('import')
            ->assertActionHidden('export')
            ->assertTableActionHidden('export', $evening);
    }

    public function test_player_list_shows_status_columns_to_both_roles(): void
    {
        $player = Player::query()->create([
            'nickname' => 'Игрок с визитом',
            'birth_day' => 5,
            'birth_month' => 4,
            'birth_year' => 1990,
            'first_visit_at' => '2026-10-05',
        ]);
        $paymentType = PaymentType::query()->create(['type' => 'Наличные']);
        $evening = Evening::query()->create(['played_at' => now()]);
        $evening->participants()->create([
            'player_id' => $player->id,
            'payment_type_id' => $paymentType->id,
        ]);

        foreach ([UserRole::Admin, UserRole::SuperAdmin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            Livewire::test(ListPlayers::class)
                ->assertTableColumnDoesNotExist('last_name')
                ->assertTableColumnDoesNotExist('firstHost.nickname')
                ->assertTableColumnExists(
                    'birthday',
                    fn ($column): bool => $column->getState() === '05.04.1990',
                    $player,
                )
                ->assertTableColumnExists(
                    'first_visit_at',
                    fn ($column): bool => $column->formatState($column->getState()) === '05.10.2026',
                    $player,
                )
                ->assertTableColumnExists(
                    'status',
                    fn ($column): bool => $column->getState() === 'new'
                        && str_contains(
                            (string) $column->formatState($column->getState()),
                            'player-status--new',
                        ),
                    $player,
                )
                ->assertTableColumnExists(
                    'activity_status',
                    fn ($column): bool => $column->getState() === 'Гость клуба'
                        && str_contains(
                            (string) $column->formatState($column->getState()),
                            'player-activity-status--guest',
                        ),
                    $player,
                );
        }
    }

    public function test_admin_cannot_open_player_statistics_or_see_its_buttons(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $player = Player::query()->create(['nickname' => 'Игрок']);

        Livewire::test(ViewPlayer::class, ['record' => $player->getRouteKey()])
            ->assertActionHidden('statistics');

        Livewire::test(PlayerAutumnCase::class, ['record' => $player->getRouteKey()])
            ->assertActionHidden('statistics');

        $this->assertFalse(PlayerStatistics::canAccess(['record' => $player]));
        $this->assertFalse(PlayerStatsOverview::canView());
        $this->assertFalse(PlayerActivityChart::canView());
        $this->assertFalse(PlayerVisitHistory::canView());

        $this->get(PlayerResource::getUrl('statistics', ['record' => $player], panel: 'admin'))
            ->assertForbidden()
            ->assertSee('Доступ ограничен');
    }

    public function test_superadmin_has_full_access(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $this->assertTrue(SourceResource::canAccess());
        $this->assertTrue(CashBook::canAccess());
        $this->assertTrue(ImportPlayers::canAccess());
        $this->assertTrue(UserResource::canAccess());
        $this->assertTrue(ClubStatsOverview::canView());

        $player = Player::query()->create(['nickname' => 'Игрок']);

        $this->assertTrue(PlayerStatistics::canAccess(['record' => $player]));
        $this->assertTrue(PlayerStatsOverview::canView());
        $this->assertTrue(PlayerActivityChart::canView());
        $this->assertTrue(PlayerVisitHistory::canView());

        $this->get(SourceResource::getUrl(panel: 'admin'))->assertOk();
        $this->get(CashBook::getUrl(panel: 'admin'))->assertOk();
        $this->get(UserResource::getUrl(panel: 'admin'))->assertOk();
        $this->get(PlayerResource::getUrl('statistics', ['record' => $player], panel: 'admin'))->assertOk();

        Livewire::test(ViewPlayer::class, ['record' => $player->getRouteKey()])
            ->assertActionVisible('statistics');

        Livewire::test(ClubWelcome::class)
            ->assertDontSee('Кассовая книга');

        Livewire::test(ListPlayers::class)
            ->assertActionVisible('import')
            ->assertActionVisible('export');

        Livewire::test(ListEvenings::class)
            ->assertActionVisible('import')
            ->assertActionVisible('export');
    }

    public function test_club_stats_handle_a_month_without_previous_financial_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ClubStatsOverview::class)
            ->assertSee('Нет данных за месяц');
    }

    public function test_superadmin_can_create_a_restricted_admin_account(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(ListUsers::class)
            ->callAction('create', data: [
                'name' => 'Обычный админ',
                'email' => 'admin@example.com',
                'role' => UserRole::Admin->value,
                'password' => 'secure-password',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::Admin->value,
        ]);
    }
}
