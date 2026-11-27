<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Players\PlayerResource;
use App\Filament\Widgets\LatestEvenings;
use App\Filament\Widgets\TodaysBirthdays;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardBirthdaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->travelTo(Carbon::parse('2026-11-26 22:30:00', 'UTC'));
    }

    public function test_widget_shows_only_today_birthdays_using_minsk_date_including_unknown_birth_year(): void
    {
        $birthday = Player::create([
            'nickname' => 'Именинник', 'first_name' => 'Анна', 'last_name' => 'Иванова',
            'birth_day' => 27, 'birth_month' => 11, 'birth_year' => 1990,
            'phone' => '+375291234567', 'telegram' => '@birthday',
        ]);
        $unknownYear = Player::create(['nickname' => 'Без года', 'birth_day' => 27, 'birth_month' => 11]);
        $otherPlayers = collect([
            Player::create(['nickname' => 'Вчера', 'birth_day' => 26, 'birth_month' => 11]),
            Player::create(['nickname' => 'Другой месяц', 'birth_day' => 27, 'birth_month' => 12]),
            Player::create(['nickname' => 'Нет даты']),
        ]);

        Livewire::test(TodaysBirthdays::class)
            ->assertCanSeeTableRecords([$birthday, $unknownYear])
            ->assertCanNotSeeTableRecords($otherPlayers)
            ->assertSee('Анна')->assertSee('Иванова')
            ->assertDontSee('+375291234567')->assertDontSee('@birthday')
            ->assertSee(PlayerResource::getUrl('view', ['record' => $birthday]));
    }

    public function test_widget_lists_all_birthdays_without_search_or_pagination_and_shows_total(): void
    {
        $players = collect(range(1, 8))->map(fn (int $number): Player => Player::create([
            'nickname' => sprintf('Игрок %02d', $number), 'birth_day' => 27, 'birth_month' => 11,
        ]));

        $component = Livewire::test(TodaysBirthdays::class)
            ->assertCanSeeTableRecords($players)
            ->assertTableColumnExists('position')
            ->assertTableColumnExists('nickname')
            ->assertTableColumnExists('first_name')
            ->assertTableColumnExists('last_name')
            ->assertTableColumnDoesNotExist('phone')
            ->assertTableColumnDoesNotExist('telegram')
            ->assertSeeHtml('aria-label="Всего именинников: 8"')
            ->assertDontSeeHtml('type="search"');

        $this->assertFalse($component->instance()->getTable()->isPaginated());
        $this->assertFalse($component->instance()->getTable()->isSearchable());
        $this->assertCount(8, $component->instance()->getTableRecords());
    }

    public function test_unknown_imported_birthday_does_not_appear_on_january_first(): void
    {
        $this->travelTo(Carbon::parse('2027-01-01 10:00:00', 'Europe/Minsk'));
        $placeholder = Player::create(['nickname' => 'Дата не указана при импорте', 'birth_day' => 1, 'birth_month' => 1, 'birth_year' => 1900]);
        $birthday = Player::create(['nickname' => 'Первое января', 'birth_day' => 1, 'birth_month' => 1]);

        Livewire::test(TodaysBirthdays::class)
            ->assertCanSeeTableRecords([$birthday])
            ->assertCanNotSeeTableRecords([$placeholder]);
    }

    public function test_widget_shows_empty_state_when_nobody_has_birthday_today(): void
    {
        Livewire::test(TodaysBirthdays::class)->assertSee('Сегодня именинников нет');
    }

    public function test_dashboard_replaces_latest_evenings_and_removes_old_subheading_for_both_roles(): void
    {
        $dashboard = new Dashboard;
        $this->assertNull($dashboard->getSubheading());
        $this->assertContains(TodaysBirthdays::class, $dashboard->getWidgets());
        $this->assertNotContains(LatestEvenings::class, $dashboard->getWidgets());

        foreach ([UserRole::Admin, UserRole::SuperAdmin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->assertTrue(TodaysBirthdays::canView());
            $this->get('/admin')->assertOk()
                ->assertSee('Именинники сегодня')
                ->assertDontSee('Последние вечера')
                ->assertDontSee('ключевые показатели и последние события');
        }
    }
}
