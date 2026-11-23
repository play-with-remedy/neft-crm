<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\CashBook;
use App\Filament\Pages\ImportEvenings;
use App\Filament\Pages\Schedule;
use App\Filament\Resources\Evenings\Pages\CreateEvening;
use App\Filament\Resources\Evenings\Pages\EditEvening;
use App\Filament\Resources\Evenings\Pages\ListEvenings;
use App\Models\Evening;
use App\Models\EveningType;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\Player;
use App\Models\Project;
use App\Models\User;
use App\Support\EveningCsvExporter;
use Database\Seeders\LocationSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private EveningType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beforeApplicationDestroyed(Repeater::fake());
        $this->seed(LocationSeeder::class);
        Carbon::setTestNow('2026-11-21 09:00:00');
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->project = Project::create(['name' => 'Клуб']);
        $this->type = EveningType::create(['name' => 'Классическая мафия']);
    }

    public function test_schedule_is_available_in_club_navigation_for_admins(): void
    {
        $this->assertTrue(Schedule::canAccess());
        $this->assertSame('Клуб', Schedule::getNavigationGroup());
        $this->get(Schedule::getUrl(panel: 'admin'))->assertOk()->assertSee('Расписание');
    }

    public function test_creating_a_scheduled_game_adds_the_same_record_to_evenings(): void
    {
        Livewire::test(Schedule::class)
            ->callAction('create', data: $this->formDetails())
            ->assertHasNoActionErrors();

        $evening = Evening::query()->sole();
        $this->assertSame('2026-11-28 19:30:00', $evening->played_at->format('Y-m-d H:i:s'));
        $this->assertSame('30.50', $evening->price);
        $this->assertNull($evening->address);
        $this->assertSame(['Neft — Manhattan; Neft'], $evening->venue_labels);
        $this->assertCount(0, $evening->participants);
        Livewire::test(ListEvenings::class)->assertCanSeeTableRecords([$evening]);
    }

    public function test_future_evening_created_through_regular_form_appears_in_schedule(): void
    {
        Livewire::test(CreateEvening::class)
            ->fillForm($this->formDetails())->call('create')->assertHasNoFormErrors();

        Livewire::test(Schedule::class)->assertCanSeeTableRecords(Evening::all());
        $this->assertSame('19:30', Evening::query()->sole()->played_at->format('H:i'));
    }

    public function test_schedule_and_evening_forms_edit_one_record_without_changing_participants(): void
    {
        $evening = Evening::create($this->details());
        $player = Player::create(['nickname' => 'Игрок']);
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        $participant = $evening->participants()->create([
            'player_id' => $player->id, 'payment_type_id' => $paymentType->id, 'paid_amount' => 12.50,
        ]);

        Livewire::test(Schedule::class)
            ->callTableAction('edit', $evening, data: [...$this->formDetails(), 'price' => 0])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Минск, ул. Примерная, 1', $evening->fresh()->address);
        $this->assertSame(['Neft — Manhattan; Neft'], $evening->fresh()->venue_labels);
        $this->assertSame('0.00', $evening->fresh()->price);
        $this->assertSame($participant->id, $evening->fresh()->participants->sole()->id);
        $this->assertSame('12.50', $participant->fresh()->payments->sole()->amount);

        Livewire::test(EditEvening::class, ['record' => $evening->getRouteKey()])
            ->fillForm(['played_at' => '2026-11-29 20:15:00', 'price' => 42.75])
            ->call('save')->assertHasNoFormErrors();

        Livewire::test(Schedule::class)->assertCanSeeTableRecords([$evening->fresh()]);
        $this->assertSame('20:15', $evening->fresh()->played_at->format('H:i'));
        $this->assertSame('42.75', $evening->fresh()->price);
        $this->assertDatabaseCount('evenings', 1);
    }

    public function test_schedule_defaults_to_today_and_future_and_can_show_history(): void
    {
        $past = Evening::create([...$this->details(), 'played_at' => '2026-11-20 19:00:00']);
        $today = Evening::create([...$this->details(), 'played_at' => '2026-11-21 10:00:00']);
        $future = Evening::create($this->details());

        Livewire::test(Schedule::class)
            ->assertCanSeeTableRecords([$today, $future])
            ->assertCanNotSeeTableRecords([$past])
            ->filterTable('period', ['from' => null, 'until' => null])
            ->assertCanSeeTableRecords([$past, $today, $future]);
    }

    public function test_schedule_requires_type_project_and_valid_non_negative_price(): void
    {
        Livewire::test(Schedule::class)
            ->callAction('create', data: [...$this->formDetails(), 'price' => -1, 'project_id' => null, 'evening_type_id' => null])
            ->assertHasActionErrors(['price' => 'min', 'project_id' => 'required', 'evening_type_id' => 'required']);
        $this->assertDatabaseCount('evenings', 0);
    }

    public function test_locked_evenings_cannot_be_edited_or_deleted_from_schedule(): void
    {
        $evening = Evening::create([...$this->details(), 'locked_at' => now()]);

        Livewire::test(Schedule::class)
            ->assertTableActionHidden('edit', $evening)
            ->assertTableActionHidden('delete', $evening);
    }

    public function test_future_evenings_and_their_advance_payments_are_excluded_from_reports(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $past = Evening::create([...$this->details(), 'played_at' => '2026-11-21 11:00:00']);
        $future = Evening::create([...$this->details(), 'played_at' => '2026-11-21 15:00:00']);
        $player = Player::create(['nickname' => 'Игрок']);
        $paymentType = PaymentType::create(['type' => 'Наличные']);
        foreach ([$past, $future] as $evening) {
            $evening->participants()->create([
                'player_id' => $player->id, 'payment_type_id' => $paymentType->id,
                'paid_amount' => $evening->is($past) ? 40 : 800,
            ]);
        }

        $this->assertSame([$past->id], Evening::query()->occurred()->pluck('id')->all());
        $this->assertSame(1, $player->participations()->count());
        $component = Livewire::test(CashBook::class)
            ->assertCanSeeTableRecords([$past])->assertCanNotSeeTableRecords([$future]);
        $this->assertSame(40.0, $component->instance()->getTotals()['revenue']);
        $this->assertCount(1, $future->fresh()->participants);
    }

    public function test_empty_scheduled_evening_round_trips_through_csv_with_time_address_and_price(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        Storage::fake('local');
        $evening = Evening::create($this->details());
        ob_start();
        EveningCsvExporter::download($evening)->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('Вечер', $csv);
        $this->assertStringContainsString('28.11.2026 19:30', $csv);
        $evening->delete();

        $database = \Mockery::mock(DB::getFacadeRoot());
        $database->shouldReceive('statement')->once()
            ->with('ALTER TABLE evenings AUTO_INCREMENT = '.($evening->id + 1))->andReturnTrue();
        DB::swap($database);
        Storage::disk('local')->put('imports/schedule.csv', $csv);
        Livewire::test(ImportEvenings::class)
            ->fillForm(['file' => ['imports/schedule.csv']])->call('import')
            ->assertHasNoFormErrors()->assertSet('result.created_evenings', 1)->assertSet('result.skipped', 0);

        $imported = Evening::query()->sole();
        $this->assertSame($evening->id, $imported->id);
        $this->assertSame('19:30', $imported->played_at->format('H:i'));
        $this->assertSame('30.50', $imported->price);
        $this->assertSame('Минск, ул. Примерная, 1', $imported->address);
    }

    private function formDetails(): array
    {
        $details = $this->details();
        unset($details['address']);
        $location = Location::query()->where('name', 'Neft')->sole();

        return [...$details, 'venues' => [[
            'location_id' => $location->id, 'hall_ids' => $location->halls()->limit(2)->pluck('id')->all(),
        ]]];
    }

    private function details(): array
    {
        return [
            'played_at' => '2026-11-28 19:30:00',
            'project_id' => $this->project->id,
            'evening_type_id' => $this->type->id,
            'address' => 'Минск, ул. Примерная, 1',
            'price' => 30.50,
        ];
    }
}
