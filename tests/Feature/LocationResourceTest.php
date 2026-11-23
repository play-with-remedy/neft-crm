<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Models\Hall;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\LocationSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beforeApplicationDestroyed(Repeater::fake());
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
    }

    public function test_seeder_creates_the_requested_locations_and_halls(): void
    {
        $this->seed(LocationSeeder::class);
        $this->assertDatabaseCount('locations', 2);
        $this->assertDatabaseCount('halls', 7);

        $neft = Location::query()->where('name', 'Neft')->sole();
        $this->assertSame('Аранская 8', $neft->address);
        $this->assertSame(['Manhattan', 'Neft', 'Brooklyn'], $neft->halls->pluck('name')->all());

        $kibitka = Location::query()->where('name', 'Кибитка')->sole();
        $this->assertSame('Октябрьская 16/28', $kibitka->address);
        $this->assertSame(['Сицилия 21', 'Детройит 30', 'Чикаго 60', 'Токио 49'], $kibitka->halls->pluck('name')->all());
    }

    public function test_seeder_can_be_repeated_without_duplicates_or_overwriting_manual_changes(): void
    {
        $this->seed(LocationSeeder::class);
        $neft = Location::query()->where('name', 'Neft')->sole();
        $neft->update(['address' => 'Обновлённый адрес']);
        $neft->halls()->create(['name' => 'Терраса']);
        $this->seed(LocationSeeder::class);

        $this->assertDatabaseCount('locations', 2);
        $this->assertDatabaseCount('halls', 8);
        $this->assertSame('Обновлённый адрес', $neft->fresh()->address);
        $this->assertTrue($neft->halls()->where('name', 'Терраса')->exists());
    }

    public function test_directory_is_in_service_data_and_available_to_superadmins(): void
    {
        $this->seed(LocationSeeder::class);
        $this->assertSame('Служебные данные', LocationResource::getNavigationGroup());
        $this->assertTrue(LocationResource::canAccess());
        $this->get(LocationResource::getUrl(panel: 'admin'))->assertOk()->assertSee('Локации');
        Livewire::test(ListLocations::class)
            ->assertCanSeeTableRecords(Location::all())
            ->assertSee('Аранская 8')->assertSee('Manhattan')->assertSee('Токио 49');
    }

    public function test_regular_admin_cannot_open_the_service_directory(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertFalse(LocationResource::canAccess());
        $this->get(LocationResource::getUrl(panel: 'admin'))->assertForbidden();
    }

    public function test_location_and_multiple_halls_can_be_created_in_one_form(): void
    {
        Livewire::test(ListLocations::class)->callAction('create', data: [
            'name' => 'Новая локация', 'address' => 'Новый адрес',
            'halls' => [['name' => 'Первый зал'], ['name' => 'Второй зал']],
        ])->assertHasNoActionErrors();

        $location = Location::query()->sole();
        $this->assertSame('Новый адрес', $location->address);
        $this->assertSame(['Первый зал', 'Второй зал'], $location->halls->pluck('name')->all());
        $this->assertDatabaseCount('locations', 1);
    }

    public function test_location_requires_an_address_and_a_unique_name(): void
    {
        $this->seed(LocationSeeder::class);
        Livewire::test(ListLocations::class)->callAction('create', data: [
            'name' => 'Neft', 'address' => null, 'halls' => [['name' => 'Зал']],
        ])->assertHasActionErrors(['name' => 'unique', 'address' => 'required']);

        $this->assertDatabaseCount('locations', 2);
        $this->assertDatabaseCount('halls', 7);
    }

    public function test_hall_names_in_one_location_cannot_repeat_ignoring_case_and_spaces(): void
    {
        Livewire::test(ListLocations::class)->callAction('create', data: [
            'name' => 'Новая локация', 'address' => 'Адрес',
            'halls' => [['name' => 'Зал'], ['name' => '  зал  ']],
        ])->assertHasActionErrors();

        $this->assertDatabaseCount('locations', 0);
        $this->assertDatabaseCount('halls', 0);
    }

    public function test_editing_one_location_address_preserves_its_halls(): void
    {
        $this->seed(LocationSeeder::class);
        $neft = Location::query()->where('name', 'Neft')->sole();
        $hallIds = $neft->halls->modelKeys();
        Livewire::test(ListLocations::class)->callTableAction('edit', $neft, data: [
            'address' => 'Аранская 10',
        ])->assertHasNoTableActionErrors();

        $this->assertSame('Аранская 10', $neft->fresh()->address);
        $this->assertSame($hallIds, $neft->fresh()->halls->modelKeys());
        $this->assertDatabaseCount('halls', 7);
    }

    public function test_different_locations_can_have_halls_with_the_same_name(): void
    {
        $first = Location::create(['name' => 'Первая', 'address' => 'Первый адрес']);
        $second = Location::create(['name' => 'Вторая', 'address' => 'Второй адрес']);
        $first->halls()->create(['name' => 'Основной']);
        $second->halls()->create(['name' => 'Основной']);

        $this->assertDatabaseCount('halls', 2);
        $this->assertSame($first->id, $first->halls->sole()->location->id);
        $this->assertSame($second->id, $second->halls->sole()->location->id);
    }

    public function test_deleting_a_location_only_deletes_its_own_halls(): void
    {
        $this->seed(LocationSeeder::class);
        $neft = Location::query()->where('name', 'Neft')->sole();
        $kibitka = Location::query()->where('name', 'Кибитка')->sole();
        Livewire::test(ListLocations::class)->callTableAction('delete', $neft)->assertHasNoTableActionErrors();

        $this->assertDatabaseCount('locations', 1);
        $this->assertDatabaseCount('halls', 4);
        $this->assertSame($kibitka->id, Location::query()->sole()->id);
        $this->assertSame(0, Hall::query()->where('location_id', $neft->id)->count());
    }
}
