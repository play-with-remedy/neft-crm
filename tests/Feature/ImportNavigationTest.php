<?php

namespace Tests\Feature;

use App\Filament\Pages\ImportEvenings;
use App\Filament\Pages\ImportPlayers;
use App\Filament\Resources\Evenings\Pages\ListEvenings;
use App\Filament\Resources\Players\Pages\ListPlayers;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PDO;
use Tests\TestCase;

class ImportNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Для теста страниц импорта требуется PDO SQLite.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_import_actions_are_available_next_to_exports(): void
    {
        Livewire::test(ListPlayers::class)
            ->assertActionExists('import')
            ->assertActionHasUrl('import', ImportPlayers::getUrl())
            ->assertActionExists('export');

        Livewire::test(ListEvenings::class)
            ->assertActionExists('import')
            ->assertActionHasUrl('import', ImportEvenings::getUrl())
            ->assertActionExists('export');
    }

    public function test_import_pages_are_not_registered_in_navigation(): void
    {
        $this->assertFalse(ImportPlayers::shouldRegisterNavigation());
        $this->assertFalse(ImportEvenings::shouldRegisterNavigation());
    }
}
