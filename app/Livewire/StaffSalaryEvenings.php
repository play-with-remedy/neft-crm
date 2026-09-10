<?php

namespace App\Livewire;

use App\Models\Evening;
use App\Models\EveningStaff;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class StaffSalaryEvenings extends Component
{
    use WithPagination;

    public int $hostId;

    public ?string $role = null;

    public ?string $periodFrom = null;

    public ?string $periodUntil = null;

    public string $periodLabel;

    public function render(): View
    {
        $staffScope = fn ($query) => $query
            ->where('host_id', $this->hostId)
            ->when($this->role, fn ($query, string $role) => $query->where('role', $role));

        $evenings = Evening::query()
            ->with([
                'eveningType:id,name',
                'project:id,name',
                'staff' => $staffScope,
            ])
            ->whereHas('staff', $staffScope)
            ->when($this->periodFrom, fn (Builder $query, string $date): Builder => $query
                ->where('played_at', '>=', $date))
            ->when($this->periodUntil, fn (Builder $query, string $date): Builder => $query
                ->where('played_at', '<', $date))
            ->orderByDesc('played_at')
            ->paginate(5, pageName: 'staffEveningsPage');

        $totals = EveningStaff::query()
            ->where('host_id', $this->hostId)
            ->when($this->role, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->whereHas('evening', fn (Builder $query): Builder => $query
                ->when($this->periodFrom, fn (Builder $query, string $date): Builder => $query
                    ->where('played_at', '>=', $date))
                ->when($this->periodUntil, fn (Builder $query, string $date): Builder => $query
                    ->where('played_at', '<', $date)))
            ->selectRaw('COUNT(DISTINCT evening_id) AS evenings_count')
            ->selectRaw('COALESCE(SUM(salary), 0) AS total_salary')
            ->first();

        return view('livewire.staff-salary-evenings', [
            'evenings' => $evenings,
            'eveningsCount' => (int) $totals->evenings_count,
            'totalSalary' => (int) $totals->total_salary,
            'roleLabels' => [
                'host' => 'Ведущий',
                'admin' => 'Админ',
                'manager' => 'Менеджер',
                'supervisor' => 'Супервайзер',
            ],
        ]);
    }
}
