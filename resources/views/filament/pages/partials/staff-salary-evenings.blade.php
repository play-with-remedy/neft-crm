<livewire:staff-salary-evenings
    :host-id="$hostId"
    :role="$role"
    :period-from="$periodFrom"
    :period-until="$periodUntil"
    :period-label="$periodLabel"
    :key="'staff-salary-evenings-'.$hostId.'-'.($role ?? 'all').'-'.($periodFrom ?? 'start').'-'.($periodUntil ?? 'end')"
/>
