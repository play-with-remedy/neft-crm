<x-filament-panels::page>
    <form wire:submit="import" class="space-y-6">
        {{ $this->form }}

        <div style="padding-top: 25px;">
            <x-filament::button type="submit">
                Импортировать игроков
            </x-filament::button>
        </div>
    </form>

    @if($this->result)
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">
                Результат импорта
            </h2>

            <div class="mt-4 space-y-1">
                <div>Создано всего: {{ $this->result['created'] }}</div>

                <div>
                    Создано с измененным ником: {{ $this->result['created_with_changed_nickname'] }}

                    @if(!empty($this->result['created_with_changed_nickname_list']))
                        <div class="text-sm text-gray-500">
                            {{ implode(', ', $this->result['created_with_changed_nickname_list']) }}
                        </div>
                    @endif
                </div>

                <div>
                    Обновлены игроки: {{ $this->result['updated_players'] }}

                    @if(!empty($this->result['updated_players_list']))
                        <div class="text-sm text-gray-500">
                            {{ implode(', ', $this->result['updated_players_list']) }}
                        </div>
                    @endif
                </div>

                <div>
                    Пропущено: {{ $this->result['skipped'] }}

                    @if(!empty($this->result['skipped_list']))
                        <div class="mt-1 space-y-1 text-sm text-gray-500">
                            @foreach($this->result['skipped_list'] as $item)
                                <div>
                                    {{ $item['nickname'] }} — {{ $item['reason'] }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 space-y-1">
                <div>
                    Пустая дата рождения заменена на 01.01.1900: {{ $this->result['empty_birthday_used'] }}

                    @if(!empty($this->result['empty_birthday_used_list']))
                        <div class="text-sm text-gray-500">
                            {{ implode(', ', $this->result['empty_birthday_used_list']) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>