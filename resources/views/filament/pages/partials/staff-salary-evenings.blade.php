<livewire:staff-salary-evenings
    :host-id="$hostId"
    :role="$role"
    :period-from="$periodFrom"
    :period-until="$periodUntil"
    :period-label="$periodLabel"
    :evening-type-id="$eveningTypeId"
    :project-id="$projectId"
    :key="'staff-salary-evenings-'.$hostId.'-'.($role ?? 'all').'-'.($periodFrom ?? 'start').'-'.($periodUntil ?? 'end').'-'.($eveningTypeId ?? 'all-types').'-'.($projectId ?? 'all-projects')"
/>
