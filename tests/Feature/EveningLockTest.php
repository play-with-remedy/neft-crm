<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\EveningLockPeriods\EveningLockPeriodResource;
use App\Filament\Resources\EveningLockPeriods\Pages\ListEveningLockPeriods;
use App\Filament\Resources\Evenings\EveningResource;
use App\Filament\Resources\Evenings\Pages\CreateEvening;
use App\Filament\Resources\Evenings\Pages\ListEvenings;
use App\Filament\Resources\Evenings\Pages\ViewEvening;
use App\Models\Evening;
use App\Models\EveningLockPeriod;
use App\Models\User;
use App\Services\EveningLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EveningLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_lock_and_unlock_an_individual_evening(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($superadmin);

        $evening = Evening::query()->create(['played_at' => '2026-10-20']);

        Livewire::test(ListEvenings::class)
            ->assertTableActionVisible('lock', $evening)
            ->callTableAction('lock', $evening, data: ['reason' => 'Данные проверены']);

        $evening->refresh();

        $this->assertNotNull($evening->locked_at);
        $this->assertSame($superadmin->id, $evening->locked_by_user_id);
        $this->assertSame('Данные проверены', $evening->lock_reason);
        $this->assertTrue(EveningResource::canEdit($evening));

        Livewire::test(ListEvenings::class)
            ->assertTableActionVisible('unlock', $evening)
            ->callTableAction('unlock', $evening);

        $evening->refresh();

        $this->assertNull($evening->locked_at);
        $this->assertNull($evening->locked_by_user_id);
        $this->assertNull($evening->lock_reason);
    }

    public function test_admin_cannot_edit_or_delete_an_individually_locked_evening(): void
    {
        $evening = Evening::query()->create([
            'played_at' => '2026-10-20',
            'locked_at' => now(),
            'lock_reason' => 'Закрыто вручную',
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->assertFalse(EveningResource::canEdit($evening));
        $this->assertFalse(EveningResource::canDelete($evening));

        Livewire::test(ListEvenings::class)
            ->assertTableActionHidden('edit', $evening)
            ->assertTableActionVisible('editPlaceholder', $evening)
            ->assertTableActionVisible('lockPlaceholder', $evening)
            ->assertTableActionVisible('exportPlaceholder', $evening)
            ->assertTableColumnExists(
                'played_at',
                fn ($column): bool => $column->getIcon($column->getState()) === 'heroicon-s-lock-closed',
                $evening,
            )
            ->assertTableActionHidden('lock', $evening)
            ->assertTableActionHidden('unlock', $evening);

        Livewire::test(ViewEvening::class, ['record' => $evening->getRouteKey()])
            ->assertActionHidden('edit')
            ->assertActionHidden('lock')
            ->assertActionHidden('unlock');

        $this->get(EveningResource::getUrl('edit', ['record' => $evening], panel: 'admin'))
            ->assertForbidden()
            ->assertSee('Доступ ограничен');
    }

    public function test_admin_can_still_edit_an_unlocked_evening(): void
    {
        $evening = Evening::query()->create(['played_at' => '2026-10-20']);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->assertTrue(EveningResource::canEdit($evening));
        $this->assertTrue(EveningResource::canDelete($evening));

        Livewire::test(ListEvenings::class)
            ->assertTableActionVisible('edit', $evening)
            ->assertTableActionHidden('editPlaceholder', $evening)
            ->assertTableActionVisible('lockPlaceholder', $evening)
            ->assertTableActionVisible('exportPlaceholder', $evening);

        $this->get(EveningResource::getUrl('edit', ['record' => $evening], panel: 'admin'))
            ->assertOk();
    }

    public function test_closed_period_locks_existing_and_future_backdated_evenings_for_admin(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($superadmin);

        Livewire::test(ListEveningLockPeriods::class)
            ->callAction('create', data: [
                'starts_on' => '2026-10-01',
                'ends_on' => '2026-10-15',
                'reason' => 'Период проверен',
            ])
            ->assertHasNoFormErrors();

        $period = EveningLockPeriod::query()->sole();
        $this->assertSame($superadmin->id, $period->locked_by_user_id);

        $existingEvening = Evening::query()->create(['played_at' => '2026-10-15']);

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->assertTrue(app(EveningLockService::class)->isLocked($existingEvening));
        $this->assertFalse(EveningResource::canEdit($existingEvening));

        Livewire::test(CreateEvening::class)
            ->fillForm(['played_at' => '2026-10-10'])
            ->call('create')
            ->assertHasFormErrors(['played_at']);

        $this->assertDatabaseMissing('evenings', ['played_at' => '2026-10-10 00:00:00']);
    }

    public function test_superadmin_can_create_an_evening_inside_a_closed_period(): void
    {
        EveningLockPeriod::query()->create([
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-15',
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(CreateEvening::class)
            ->fillForm(['played_at' => '2026-10-10'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('evenings', ['played_at' => '2026-10-10 00:00:00']);
    }

    public function test_opening_a_period_does_not_remove_an_individual_lock(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($superadmin);

        $period = EveningLockPeriod::query()->create([
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-15',
            'locked_by_user_id' => $superadmin->id,
        ]);

        $evening = Evening::query()->create([
            'played_at' => '2026-10-10',
            'locked_at' => now(),
            'locked_by_user_id' => $superadmin->id,
        ]);

        Livewire::test(ListEveningLockPeriods::class)
            ->callTableAction('delete', $period);

        $this->assertDatabaseMissing('evening_lock_periods', ['id' => $period->id]);
        $this->assertTrue(app(EveningLockService::class)->isLocked($evening->fresh()));
    }

    public function test_admin_cannot_access_period_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->assertFalse(EveningLockPeriodResource::canAccess());
        $this->get(EveningLockPeriodResource::getUrl(panel: 'admin'))
            ->assertForbidden()
            ->assertSee('Доступ ограничен');

        Livewire::test(ListEvenings::class)
            ->assertActionHidden('lock_periods');
    }
}
