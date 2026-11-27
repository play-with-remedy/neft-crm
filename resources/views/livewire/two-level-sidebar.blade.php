<div>
    <aside
        id="fi-main-sidebar"
        class="fi-sidebar fi-main-sidebar crm-sidebar"
        x-data="crmTwoLevelSidebar(@js(array_column($groups, 'key')), @js($initialGroup), @js($activeGroup), @js($storageKey))"
        x-cloak="-lg"
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen, 'crm-sidebar-collapsed': selected === null }"
        x-on:keydown.escape.stop="$store.sidebar.close()"
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_START) }}

        <div class="fi-sidebar-header-ctn">
            <header class="fi-sidebar-header">
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_BEFORE) }}
                <div class="fi-sidebar-header-logo-ctn">
                    @if ($homeUrl = filament()->getHomeUrl())
                        <a {{ \Filament\Support\generate_href_html($homeUrl) }}><x-filament-panels::logo /></a>
                    @else
                        <x-filament-panels::logo />
                    @endif
                </div>
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_AFTER) }}
                <x-filament::icon-button
                    icon="heroicon-o-x-mark"
                    color="gray"
                    label="Закрыть меню"
                    class="crm-sidebar-mobile-close"
                    x-on:click="$store.sidebar.close()"
                />
            </header>
        </div>

        @if (filament()->hasTenancy() && filament()->hasTenantMenu())
            <x-filament-panels::tenant-menu />
        @endif

        @if (filament()->isGlobalSearchEnabled() && filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Sidebar)
            @livewire(\Filament\Livewire\GlobalSearch::class)
        @endif

        <div class="crm-sidebar-layout">
            <nav class="crm-sidebar-rail" aria-label="Группы разделов">
                @foreach ($groups as $group)
                    <button
                        id="crm-sidebar-button-{{ $group['key'] }}"
                        type="button"
                        class="crm-sidebar-group-button {{ $group['key'] === $initialGroup ? 'is-selected' : '' }}"
                        data-sidebar-group="{{ $group['key'] }}"
                        aria-label="{{ $group['label'] }}"
                        aria-controls="crm-sidebar-panel-{{ $group['key'] }}"
                        aria-pressed="{{ $group['key'] === $initialGroup ? 'true' : 'false' }}"
                        aria-expanded="{{ $group['key'] === $initialGroup ? 'true' : 'false' }}"
                        x-bind:aria-pressed="selected === @js($group['key'])"
                        x-bind:aria-expanded="selected === @js($group['key'])"
                        x-bind:class="{ 'is-selected': selected === @js($group['key']) }"
                        x-on:click="select(@js($group['key']))"
                        x-on:keydown.arrow-down.prevent="focusGroup($event.currentTarget, 1)"
                        x-on:keydown.arrow-up.prevent="focusGroup($event.currentTarget, -1)"
                        x-tooltip="{ content: @js($group['label']), placement: 'right', theme: $store.theme }"
                    >
                        {{ \Filament\Support\generate_icon_html($group['icon'], size: \Filament\Support\Enums\IconSize::Large) }}
                    </button>
                @endforeach
            </nav>

            <nav class="fi-sidebar-nav crm-sidebar-menu" aria-label="Разделы выбранной группы" x-ref="menu">
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

                @foreach ($groups as $group)
                    <section
                        id="crm-sidebar-panel-{{ $group['key'] }}"
                        class="crm-sidebar-panel"
                        data-sidebar-panel="{{ $group['key'] }}"
                        aria-labelledby="crm-sidebar-button-{{ $group['key'] }}"
                        x-bind:hidden="selected !== @js($group['key'])"
                        @if ($group['key'] !== $initialGroup) hidden @endif
                    >
                        <h2 class="crm-sidebar-heading">{{ $group['label'] }}</h2>
                        <ul class="crm-sidebar-items">
                            <x-filament-panels::sidebar.group
                                :items="$group['items']"
                                :label="null"
                                :collapsible="false"
                                :sidebar-collapsible="false"
                            />
                        </ul>
                    </section>
                @endforeach

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
            </nav>
        </div>

        @php
            $hasNotifications = filament()->hasDatabaseNotifications()
                && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Sidebar;
            $hasUserMenu = filament()->hasUserMenu()
                && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Sidebar;
        @endphp
        @if (filament()->auth()->check() && ($hasNotifications || $hasUserMenu))
            <div class="fi-sidebar-footer">
                @if ($hasNotifications)
                    @livewire(filament()->getDatabaseNotificationsLivewireComponent(), ['lazy' => filament()->hasLazyLoadedDatabaseNotifications()])
                @endif
                @if ($hasUserMenu)
                    <x-filament-panels::user-menu />
                @endif
            </div>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
    </aside>

    <x-filament-actions::modals />
</div>
