<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\CashBook;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Players\PlayerResource;
use App\Filament\Resources\Sources\SourceResource;
use App\Livewire\TwoLevelSidebar;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TwoLevelSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_uses_custom_sidebar_with_only_authorized_groups_for_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertSame(TwoLevelSidebar::class, Filament::getPanel('admin')->getSidebarLivewireComponent());

        $component = Livewire::test(TwoLevelSidebar::class)
            ->assertSee('Группы разделов')->assertSee('Клуб')->assertSee('Продажи')
            ->assertDontSee('Отчеты')->assertDontSee('Служебные данные')->assertDontSee('Системная таблица');
        $groups = $component->instance()->render()->getData()['groups'];

        $this->assertSame(['Клуб', 'Продажи'], array_column($groups, 'label'));
        $this->assertSame('Главная', $groups[0]['items'][0]->getLabel());
    }

    public function test_superadmin_keeps_all_navigation_and_alphabetical_report_and_directory_order(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->get('/admin')->assertOk();
        $groups = (new TwoLevelSidebar)->render()->getData()['groups'];
        $byLabel = collect($groups)->keyBy('label');

        $this->assertTrue($byLabel->has(['Клуб', 'Продажи', 'Отчеты', 'Служебные данные', 'Системная таблица']));
        $labels = fn (string $group): array => array_map(fn (NavigationItem $item): string => $item->getLabel(), $byLabel[$group]['items']);
        $this->assertSame([
            'Анализ LTV', 'Аналитика игроков', 'Воронка', 'Зарплаты сотрудников', 'Кассовая книга',
            'Месячные расходы', 'Привлечение игроков', 'Статистика мероприятий', 'Финансы по месяцам',
        ], $labels('Отчеты'));
        $this->assertSame([
            'Админы', 'Источники', 'Локации', 'Проекты', 'Статьи расходов', 'Типы вечера', 'Типы оплаты', 'Финансовые категории',
        ], $labels('Служебные данные'));
        $this->assertCount(count($groups), array_unique(array_column($groups, 'key')));
    }

    #[DataProvider('navigationRoutes')]
    public function test_direct_links_select_the_correct_group_and_keep_current_page_highlighted(string $page, string $label): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        $url = $page::getUrl(panel: 'admin');
        $response = $this->get($url)->assertOk();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);
        $selected = $xpath->query('//button[@data-sidebar-group and @aria-pressed="true"]');
        $this->assertSame(1, $selected->length);
        $this->assertSame($label, $selected->item(0)->getAttribute('aria-label'));
        $panelId = $selected->item(0)->getAttribute('aria-controls');
        $panel = $document->getElementById($panelId);
        $this->assertNotNull($panel);
        $this->assertFalse($panel->hasAttribute('hidden'));
        $this->assertSame(1, $xpath->query('//*[@id="'.$panelId.'"]//a[@aria-current="page"]')->length);
    }

    public static function navigationRoutes(): array
    {
        return [
            [Dashboard::class, 'Клуб'],
            [CashBook::class, 'Отчеты'],
            [SourceResource::class, 'Служебные данные'],
            [PlayerResource::class, 'Клуб'],
        ];
    }
}
