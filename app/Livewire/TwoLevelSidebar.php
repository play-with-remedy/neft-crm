<?php

namespace App\Livewire;

use Filament\Livewire\Sidebar;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\View\View;

class TwoLevelSidebar extends Sidebar
{
    private const GROUP_ICONS = [
        'Клуб' => 'heroicon-o-user-group',
        'Продажи' => 'heroicon-o-briefcase',
        'Отчеты' => 'heroicon-o-chart-bar',
        'Синхронизация' => 'heroicon-o-arrows-right-left',
        'Системная таблица' => 'heroicon-o-circle-stack',
        'Служебные данные' => 'heroicon-o-cog-6-tooth',
    ];

    public function render(): View
    {
        // Use Filament's authorized navigation, including its ordering and item metadata.
        $navigation = collect(filament()->getNavigation());
        $ungrouped = $navigation->filter(fn (NavigationGroup $group): bool => blank($group->getLabel()))
            ->flatMap(fn (NavigationGroup $group) => $group->getItems())->all();
        $groups = $navigation->filter(fn (NavigationGroup $group): bool => filled($group->getLabel()))
            ->map(fn (NavigationGroup $group): array => [
                'key' => 'group-'.substr(md5($group->getLabel()), 0, 12),
                'label' => $group->getLabel(),
                'icon' => $group->getIcon() ?? self::GROUP_ICONS[$group->getLabel()] ?? 'heroicon-o-folder',
                'items' => array_values([...$group->getItems()]),
            ])->values()->all();

        // The dashboard belongs in the club menu rather than creating an unnamed rail button.
        if ($ungrouped !== []) {
            $clubIndex = array_search('Клуб', array_column($groups, 'label'), true);

            if ($clubIndex !== false) {
                $groups[$clubIndex]['items'] = [...$ungrouped, ...$groups[$clubIndex]['items']];
            } else {
                array_unshift($groups, [
                    'key' => 'group-home', 'label' => 'Главная',
                    'icon' => 'heroicon-o-home', 'items' => $ungrouped,
                ]);
            }
        }

        $activeGroup = null;

        foreach ($groups as $group) {
            if (collect($group['items'])->contains(fn (NavigationItem $item): bool => $item->isActive() || $item->isChildItemsActive())) {
                $activeGroup = $group['key'];

                break;
            }
        }

        return view('livewire.two-level-sidebar', [
            'groups' => $groups,
            'activeGroup' => $activeGroup,
            'initialGroup' => $activeGroup ?? ($groups[0]['key'] ?? null),
            'storageKey' => 'crm:sidebar-group:'.filament()->getId().':'.filament()->auth()->id(),
        ]);
    }
}
