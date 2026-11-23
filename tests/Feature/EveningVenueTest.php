<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ImportEvenings;
use App\Filament\Pages\Schedule;
use App\Filament\Resources\Evenings\Pages\EditEvening;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Models\Evening;
use App\Models\EveningType;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use App\Support\EveningCsvExporter;
use Database\Seeders\LocationSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EveningVenueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beforeApplicationDestroyed(Repeater::fake());
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->seed(LocationSeeder::class);
    }

    public function test_multiple_locations_and_halls_can_be_saved_and_edited_through_both_forms(): void
    {
        $selections = $this->selections();
        Livewire::test(Schedule::class)->callAction('create', data: [
            'played_at' => '2030-11-28 19:30:00',
            'project_id' => Project::create(['name' => 'Клуб'])->id,
            'evening_type_id' => EveningType::create(['name' => 'Мафия'])->id,
            'venues' => $selections,
        ])->assertHasNoActionErrors();

        $evening = Evening::query()->sole();
        $this->assertCount(2, $evening->venues);
        $this->assertCount(4, DB::table('evening_venue_hall')->get());
        Livewire::test(Schedule::class)->assertSee('Manhattan')->assertSee('Сицилия, #21');

        $ids = $evening->venues->pluck('id', 'location_id')->all();
        Livewire::test(EditEvening::class, ['record' => $evening->id])
            ->assertFormSet(['venues' => $selections])
            ->fillForm(['venues' => array_reverse($selections)])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame($ids, $evening->fresh()->venues->pluck('id', 'location_id')->all());

        $selections[0]['hall_ids'] = [$selections[0]['hall_ids'][1]];
        Livewire::test(Schedule::class)->mountTableAction('edit', $evening)
            ->fillForm(['venues' => []])->fillForm(['venues' => [$selections[0]]])->callMountedTableAction()
            ->assertHasNoTableActionErrors();
        $this->assertSame($selections[0]['hall_ids'], $evening->fresh()->venues->sole()->halls->modelKeys());
        $this->assertDatabaseCount('evening_venues', 1);
        $this->assertDatabaseCount('evening_venue_hall', 1);
    }

    public function test_form_rejects_halls_from_another_location_without_creating_an_evening(): void
    {
        $selections = $this->selections();
        $selections[0]['hall_ids'] = $selections[1]['hall_ids'];
        Livewire::test(Schedule::class)->callAction('create', data: [
            'played_at' => '2030-11-28 19:30:00',
            'project_id' => Project::create(['name' => 'Клуб'])->id,
            'evening_type_id' => EveningType::create(['name' => 'Мафия'])->id,
            'venues' => [$selections[0]],
        ])->assertHasActionErrors();
        $this->assertDatabaseCount('evenings', 0);
    }

    public function test_duplicate_locations_and_wrong_halls_are_rejected_without_changing_existing_selections(): void
    {
        $evening = Evening::create(['played_at' => '2030-11-28 19:30:00']);
        $selections = $this->selections();
        $evening->syncVenueSelections($selections);
        foreach ([[$selections[0], $selections[0]], [
            $selections[0], ['location_id' => $selections[1]['location_id'], 'hall_ids' => $selections[0]['hall_ids']],
        ]] as $invalid) {
            try {
                $evening->syncVenueSelections($invalid);
                $this->fail('Invalid venue selections should be rejected.');
            } catch (ValidationException) {
                $this->assertSame($selections, $evening->fresh()->venues->map(fn ($venue): array => [
                    'location_id' => $venue->location_id, 'hall_ids' => $venue->halls->modelKeys(),
                ])->all());
            }
        }
    }

    public function test_evening_without_locations_can_be_edited_without_an_old_address_field(): void
    {
        $evening = Evening::create(['played_at' => '2030-11-28 19:30:00']);
        Livewire::test(EditEvening::class, ['record' => $evening->id])
            ->assertDontSee('Ранее указанный адрес')
            ->fillForm(['price' => 25])->call('save')->assertHasNoFormErrors();
        $this->assertSame([], $evening->fresh()->venue_labels);
        $this->assertFalse(Schema::hasColumn('evenings', 'address'));
        $this->assertSame('25.00', $evening->fresh()->price);
    }

    public function test_schedule_preloads_static_locations_and_all_halls_without_live_updates(): void
    {
        $component = Livewire::test(Schedule::class)->mountAction('create');
        $page = $component->instance();
        $schema = (new \ReflectionMethod(Schedule::class, 'getMountedActionSchema'))->invoke($page);
        $venues = collect($schema->getComponents())->first(fn ($field) => $field instanceof Repeater);
        $row = array_key_first($venues->getRawState());
        [$location, $halls] = $venues->getChildSchema($row)->getComponents();

        $this->assertFalse($location->hasDynamicOptions());
        $this->assertFalse($location->isLive());
        $this->assertFalse($location->isSearchable());
        $this->assertCount(2, $location->getOptions());
        view()->share('errors', new ViewErrorBag);
        $html = $halls->toHtml();
        $names = collect($halls->getViewData()['hallsByLocation'])->flatten(1)->pluck('name')->all();
        foreach (['Manhattan', 'Brooklyn', 'Сицилия, #21', 'Токио, #49'] as $name) {
            $this->assertContains($name, $names);
        }
        $this->assertStringNotContainsString('getFormSelectOptions', $html);
        $this->assertStringNotContainsString('.live', $html);
    }

    public function test_used_locations_and_halls_cannot_be_removed_from_directory(): void
    {
        $evening = Evening::create(['played_at' => '2030-11-28 19:30:00']);
        $evening->syncVenueSelections($this->selections());
        $location = $evening->venues->first()->location;
        $this->assertFalse(LocationResource::canDelete($location));
        Livewire::test(ListLocations::class)->assertTableActionHidden('delete', $location);

        Livewire::test(ListLocations::class)->mountTableAction('edit', $location)
            ->fillForm(['halls' => []])->fillForm([
                'address' => 'Новый адрес', 'halls' => [['name' => 'Заменённый зал']],
            ])->callMountedTableAction()->assertHasTableActionErrors();
        $this->assertSame('Аранская 8', $location->fresh()->address);
        $this->assertDatabaseCount('evening_venue_hall', 4);
        $this->assertDatabaseCount('halls', 7);
    }

    public function test_seeder_renames_old_kibitka_halls_without_changing_their_ids_or_links(): void
    {
        $location = Location::where('name', 'Кибитка')->sole();
        $hall = $location->halls->first();
        $hall->update(['name' => 'Сицилия 21']);
        $evening = Evening::create(['played_at' => '2030-11-28 19:30:00']);
        $evening->syncVenueSelections([['location_id' => $location->id, 'hall_ids' => [$hall->id]]]);
        $this->seed(LocationSeeder::class);
        $this->assertSame('Сицилия, #21', $hall->fresh()->name);
        $this->assertSame([$hall->id], $evening->fresh()->venues->sole()->halls->modelKeys());
        $this->assertDatabaseCount('halls', 7);
    }

    public function test_csv_restores_multiple_locations_and_halls_and_supports_old_files(): void
    {
        Storage::fake('local');
        $evening = Evening::create(['played_at' => '2030-11-28 19:30:00']);
        $evening->syncVenueSelections($this->selections());
        $expected = $evening->venueExportData();
        ob_start();
        EveningCsvExporter::download($evening)->sendContent();
        $csv = ob_get_clean();
        $evening->syncVenueSelections([]);
        Location::query()->delete();

        $database = \Mockery::mock(DB::getFacadeRoot());
        $database->shouldReceive('statement')->twice()
            ->with('ALTER TABLE evenings AUTO_INCREMENT = '.($evening->id + 1))->andReturnTrue();
        DB::swap($database);
        Storage::disk('local')->put('imports/venues.csv', $csv);
        Livewire::test(ImportEvenings::class)->fillForm(['file' => ['imports/venues.csv']])->call('import')
            ->assertSet('result.updated_evenings', 1)->assertSet('result.skipped', 0);
        $this->assertSame($expected, $evening->fresh()->venueExportData());

        $legacy = "ID вечера;Дата проведения;Тип записи\n{$evening->id};28.11.2030 20:15;Вечер\n";
        Storage::disk('local')->put('imports/legacy.csv', $legacy);
        Livewire::test(ImportEvenings::class)->fillForm(['file' => ['imports/legacy.csv']])->call('import')
            ->assertSet('result.updated_evenings', 1)->assertSet('result.skipped', 0);
        $this->assertSame($expected, $evening->fresh()->venueExportData());
    }

    private function selections(): array
    {
        return Location::orderBy('id')->with('halls')->get()->map(fn (Location $location): array => [
            'location_id' => $location->id, 'hall_ids' => $location->halls->take(2)->modelKeys(),
        ])->all();
    }
}
