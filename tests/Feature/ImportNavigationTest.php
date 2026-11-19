<?php

namespace Tests\Feature;

use App\Filament\Pages\ImportEvenings;
use App\Filament\Pages\ImportPlayers;
use App\Filament\Resources\Evenings\Pages\ListEvenings;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Models\Evening;
use App\Models\ExpenseCategory;
use App\Models\Host;
use App\Models\PaymentType;
use App\Models\Player;
use App\Support\EveningCsvExporter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function test_evening_csv_preserves_all_record_types_and_notes_without_new_player_column(): void
    {
        Storage::fake('local');
        $evening = Evening::create(['played_at' => '2026-09-08 19:00:00']);
        $host = Host::create(['nickname' => 'Host']);
        $player = Player::create(['nickname' => 'Player']);
        $paymentType = PaymentType::create(['type' => 'Cash']);
        $category = ExpenseCategory::create(['name' => 'Rent']);

        $evening->staff()->create(['host_id' => $host->id, 'role' => 'host', 'salary' => 20.15]);
        $evening->participants()->create([
            'player_id' => $player->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 32.50,
            'note' => 'Комментарий; без смещения колонок',
        ]);
        $evening->expenses()->create(['expense_category_id' => $category->id, 'amount' => 10.25]);

        ob_start();
        EveningCsvExporter::download($evening)->sendContent();
        $csv = ob_get_clean();

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        rewind($handle);
        $headers = fgetcsv($handle, separator: ';');
        $this->assertNotContains('Новый игрок', $headers);
        $rows = [];

        while (($row = fgetcsv($handle, separator: ';')) !== false) {
            $this->assertCount(count($headers), $row);
            $rows[] = array_combine($headers, $row);
        }

        fclose($handle);
        $this->assertSame(['Команда', 'Игрок', 'Расход'], array_column($rows, 'Тип записи'));
        $this->assertSame('Комментарий; без смещения колонок', $rows[1]['Примечание']);

        // The importer resets MySQL's sequence; SQLite needs no equivalent statement.
        $database = \Mockery::mock(DB::getFacadeRoot());
        $database->shouldReceive('statement')
            ->once()
            ->with('ALTER TABLE evenings AUTO_INCREMENT = '.($evening->id + 1))
            ->andReturnTrue();
        DB::swap($database);

        Storage::disk('local')->put('imports/evenings.csv', $csv);

        Livewire::test(ImportEvenings::class)
            ->fillForm(['file' => ['imports/evenings.csv']])
            ->call('import')
            ->assertHasNoFormErrors()
            ->assertSet('result.updated_evenings', 1)
            ->assertSet('result.skipped', 0);

        $evening->refresh();
        $this->assertSame('20.15', $evening->staff->sole()->salary);
        $this->assertSame('10.25', $evening->expenses->sole()->amount);
        $participant = $evening->participants->sole();
        $this->assertSame('32.50', $participant->paid_amount);
        $this->assertSame('Комментарий; без смещения колонок', $participant->note);
        $this->assertSame('32.50', $participant->payments->sole()->amount);
    }
}
