<x-filament-panels::page>
    <style>
        .nickname-preview-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            align-items: start;
        }

        .nickname-preview-list {
            max-height: 420px;
            overflow-y: auto;
        }

        .nickname-decision-select {
            width: 100%;
            border: 1px solid #52525b;
            border-radius: 8px;
            background-color: #18181b;
            color: #ffffff;
            color-scheme: dark;
            font-size: 14px;
        }

        .nickname-decision-select option {
            background-color: #18181b;
            color: #ffffff;
        }

        @media (max-width: 1023px) {
            .nickname-preview-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <form wire:submit="previewNicknames" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Проверить список
        </x-filament::button>
    </form>

    @if ($this->preview)
        <div class="mt-8 space-y-6">
            <div class="nickname-preview-grid">
                <div class="overflow-hidden rounded-xl border border-gray-700 bg-gray-950">
                    <div class="flex items-center justify-between border-b border-gray-700 bg-gray-900 px-4 py-3">
                        <div class="font-semibold text-white">Уже есть</div>
                        <div class="rounded-full bg-gray-700 px-2.5 py-1 text-xs font-bold text-white">
                            {{ count($this->preview['exact']) }}
                        </div>
                    </div>

                    <div class="nickname-preview-list divide-y divide-gray-800">
                        @forelse ($this->preview['exact'] as $item)
                            <div class="px-4 py-3">
                                <div class="font-medium text-white">{{ $item['existing'] }}</div>
                                @if ($item['nickname'] !== $item['existing'])
                                    <div class="mt-1 text-xs text-gray-500">В списке: {{ $item['nickname'] }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-gray-500">Совпадений нет</div>
                        @endforelse
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-yellow-800 bg-gray-950">
                    <div class="flex items-center justify-between border-b border-yellow-900 bg-yellow-950/40 px-4 py-3">
                        <div class="font-semibold text-yellow-300">Похожие</div>
                        <div class="rounded-full bg-yellow-900 px-2.5 py-1 text-xs font-bold text-yellow-200">
                            {{ count($this->preview['similar']) }}
                        </div>
                    </div>

                    <div class="nickname-preview-list divide-y divide-gray-800">
                        @forelse ($this->preview['similar'] as $index => $item)
                            <div class="space-y-2 px-4 py-3">
                                <div class="font-medium text-white">{{ $item['nickname'] }}</div>
                                <select wire:model="decisions.{{ $index }}" class="nickname-decision-select">
                                    @foreach ($item['candidates'] as $candidate)
                                        <option value="match:{{ $candidate['id'] }}">
                                            Это {{ $candidate['nickname'] }}
                                        </option>
                                    @endforeach
                                    <option value="create">Создать как нового</option>
                                    <option value="skip">Пропустить</option>
                                </select>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-gray-500">Похожих ников нет</div>
                        @endforelse
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-green-800 bg-gray-950">
                    <div class="flex items-center justify-between border-b border-green-900 bg-green-950/40 px-4 py-3">
                        <div class="font-semibold text-green-300">Новые</div>
                        <div class="rounded-full bg-green-900 px-2.5 py-1 text-xs font-bold text-green-200">
                            {{ count($this->preview['new']) }}
                        </div>
                    </div>

                    <div class="nickname-preview-list divide-y divide-gray-800">
                        @forelse ($this->preview['new'] as $nickname)
                            <div class="px-4 py-3 font-medium text-white">{{ $nickname }}</div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-gray-500">Новых игроков нет</div>
                        @endforelse
                    </div>
                </div>
            </div>

            @if ($this->preview['duplicates'])
                <div class="rounded-xl border border-gray-700 bg-gray-900 px-4 py-3 text-sm text-gray-400">
                    <span class="font-medium text-white">Дубли внутри списка: {{ count($this->preview['duplicates']) }}</span>
                    <span class="ml-2">{{ implode(', ', $this->preview['duplicates']) }}</span>
                </div>
            @endif

            <div style="margin-top: 28px;">
                <x-filament::button wire:click="importNicknames" color="success">
                    Подтвердить импорт
                </x-filament::button>
            </div>
        </div>
    @endif

    @if ($this->result)
        <div class="mt-8 rounded-xl border border-gray-700 bg-gray-900 p-5">
            <div class="text-lg font-semibold text-white">Результат импорта</div>
            <div class="mt-3 space-y-1 text-sm text-gray-300">
                <div>Создано: {{ count($this->result['created']) }}</div>
                <div>Совпало с существующими: {{ $this->result['matched'] }}</div>
                <div>Пропущено: {{ $this->result['skipped'] }}</div>
            </div>
            @if ($this->result['created'])
                <div class="mt-3 text-sm text-green-300">{{ implode(', ', $this->result['created']) }}</div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
