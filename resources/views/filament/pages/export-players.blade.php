<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Выгрузка всех игроков в CSV-файл для Excel.
        </p>
        <div style="padding-top: 25px;">
            <x-filament::button
                wire:click="export"
                icon="heroicon-o-arrow-down-tray"
            >
        
            Скачать CSV
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>