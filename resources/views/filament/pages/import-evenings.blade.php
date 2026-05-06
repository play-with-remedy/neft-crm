<x-filament-panels::page>
    <form wire:submit="import" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">
            Импортировать CSV
        </x-filament::button>
    </form>

    @if ($result)
        <div class="mt-8 space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-lg font-bold">
                    Результат импорта
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-5">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Вечеров обработано</div>
                        <div class="text-2xl font-bold">{{ $result['imported_evenings'] ?? 0 }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Команда создана</div>
                        <div class="text-2xl font-bold">{{ $result['created_staff'] ?? 0 }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Игроков добавлено</div>
                        <div class="text-2xl font-bold">{{ $result['created_participants'] ?? 0 }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Расходов добавлено</div>
                        <div class="text-2xl font-bold">{{ $result['created_expenses'] ?? 0 }}</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Ошибок / пропусков</div>
                        <div class="text-2xl font-bold">{{ $result['skipped'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Создано вечеров</div>
                        <div class="text-2xl font-bold">{{ $result['created_evenings'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-sm text-gray-500">Обновлено вечеров</div>
                        <div class="text-2xl font-bold">{{ $result['updated_evenings'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            @if (! empty($result['skipped_list']))
                <div class="rounded-xl border border-danger-200 bg-danger-50 p-6 shadow-sm dark:border-danger-700 dark:bg-gray-900">
                    <h3 class="text-lg font-bold text-danger-700 dark:text-danger-400">
                        Ошибки и пропущенные строки
                    </h3>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-danger-200 dark:border-danger-700">
                                    <th class="py-2 pr-4">Запись</th>
                                    <th class="py-2 pr-4">Причина</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($result['skipped_list'] as $item)
                                    <tr class="border-b border-danger-100 dark:border-gray-800">
                                        <td class="py-2 pr-4">
                                            {{ $item['item'] ?? 'unknown' }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            {{ $item['reason'] ?? '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>