<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            locationId: $wire.$entangle(@js(str($getStatePath())->beforeLast('.')->append('.location_id')->toString())),
            hallsByLocation: @js($hallsByLocation),
            init() {
                this.$watch('locationId', (value, previous) => {
                    if (String(value ?? '') !== String(previous ?? '')) this.state = []
                })
            },
            get halls() { return this.hallsByLocation[String(this.locationId)] ?? [] },
            get selectedNames() { return this.halls.filter(hall => this.isSelected(hall.id)).map(hall => hall.name) },
            isSelected(id) { return (this.state ?? []).some(value => String(value) === String(id)) },
            toggleHall(id) {
                this.state = this.isSelected(id)
                    ? this.state.filter(value => String(value) !== String(id))
                    : [...(this.state ?? []), id]
            },
        }"
    >
        <x-filament::dropdown placement="bottom-start" width="xs" max-height="16rem" style="width: 100%">
            <x-slot name="trigger">
                <x-filament::input.wrapper :valid="! $field->hasErrorForPath($getStatePath())">
                    <button
                        type="button"
                        id="{{ $getId() }}"
                        x-bind:disabled="! locationId"
                        style="display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; min-height: 2.25rem; padding: .375rem .75rem; text-align: left"
                    >
                        <span x-text="selectedNames.length ? selectedNames.join(', ') : (locationId ? 'Выберите залы' : 'Сначала выберите локацию')"></span>
                        <x-filament::icon icon="heroicon-m-chevron-down" style="width: 1rem; height: 1rem; flex-shrink: 0" />
                    </button>
                </x-filament::input.wrapper>
            </x-slot>

            <div style="padding: .5rem">
                <template x-for="hall in halls" :key="hall.id">
                    <label style="display: flex; align-items: center; gap: .75rem; padding: .5rem; cursor: pointer">
                        <input type="checkbox" class="fi-checkbox-input" :checked="isSelected(hall.id)" x-on:change="toggleHall(hall.id)" />
                        <span x-text="hall.name"></span>
                    </label>
                </template>
                <p x-show="! halls.length" style="padding: .5rem">Нет доступных залов</p>
            </div>
        </x-filament::dropdown>
    </div>
</x-dynamic-component>
