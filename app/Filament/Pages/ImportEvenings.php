<?php

namespace App\Filament\Pages;

use App\Models\Evening;
use App\Models\EveningType;
use App\Models\ExpenseCategory;
use App\Models\Host;
use App\Models\Location;
use App\Models\PaymentType;
use App\Models\Player;
use App\Models\Project;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ImportEvenings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Импорт вечеров';

    protected static ?string $title = 'Импорт вечеров';

    protected static UnitEnum|string|null $navigationGroup = 'Синхронизация';

    protected string $view = 'filament.pages.import-evenings';

    public ?array $data = [];

    public ?array $result = null;

    private const HEADERS = [
        'evening_id' => 'ID вечера',
        'played_at' => 'Дата проведения',
        'location' => 'Локация',
        'evening_type' => 'Тип вечера',
        'project' => 'Проект',
        'record_type' => 'Тип записи',
        'name' => 'Имя',
        'role' => 'Роль',
        'salary' => 'Зарплата',
        'expense_category' => 'Статья расхода',
        'expense_amount' => 'Сумма расхода',
        'payment_type' => 'Тип оплаты',
        'paid_amount' => 'Сумма оплаты',
        'is_new_player' => 'Новый игрок',
        'is_full_payment' => 'Полная оплата',
        'note' => 'Примечание',
    ];

    private const REQUIRED_HEADERS = [
        'evening_id',
        'played_at',
        'record_type',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\FileUpload::make('file')
                    ->label('CSV файл с вечерами')
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/vnd.ms-excel',
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $this->result = null;

        $data = $this->form->getState();

        $filePath = Storage::disk('local')->path($data['file']);

        if (! file_exists($filePath)) {
            Notification::make()
                ->title('Файл не найден')
                ->danger()
                ->send();

            return;
        }

        $file = fopen($filePath, 'r');

        $firstLine = fgets($file);

        if ($firstLine === false) {
            Notification::make()
                ->title('Не удалось прочитать заголовки CSV')
                ->danger()
                ->send();

            fclose($file);

            return;
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        rewind($file);

        $headers = fgetcsv($file, 0, $delimiter);

        if (! $headers) {
            Notification::make()
                ->title('Не удалось прочитать заголовки CSV')
                ->danger()
                ->send();

            fclose($file);

            return;
        }

        $headers = array_map(function ($header) {
            $header = trim($header);
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            return trim($header, "\"' \t\n\r\0\x0B");
        }, $headers);

        foreach (self::REQUIRED_HEADERS as $requiredHeaderKey) {
            $requiredHeader = self::HEADERS[$requiredHeaderKey];

            if (! in_array($requiredHeader, $headers, true)) {
                Notification::make()
                    ->title("В CSV должна быть колонка {$requiredHeader}")
                    ->danger()
                    ->send();

                fclose($file);

                return;
            }
        }

        $rowsByEvening = [];
        $skipped = 0;
        $skippedList = [];

        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            if (count($row) !== count($headers)) {
                $skipped++;
                $this->addSkipped($skippedList, '', 'неверное количество колонок');
                continue;
            }

            $rowData = array_combine($headers, $row);

            $eveningId = trim($rowData[self::HEADERS['evening_id']] ?? '');

            if ($eveningId === '') {
                $skipped++;
                $this->addSkipped($skippedList, '', 'не указан ID вечера');
                continue;
            }

            $rowsByEvening[$eveningId][] = $rowData;
        }

        fclose($file);

        $createdEvenings = 0;
        $updatedEvenings = 0;
        $createdStaff = 0;
        $createdParticipants = 0;
        $createdExpenses = 0;

        foreach ($rowsByEvening as $eveningId => $rows) {
            $firstRow = $rows[0];

            $playedAt = $this->parseDateTime($firstRow[self::HEADERS['played_at']] ?? null);

            if (! $playedAt) {
                $skipped++;
                $this->addSkipped($skippedList, (string) $eveningId, 'неверная дата проведения');
                continue;
            }

            $eveningData = [
                'played_at' => $playedAt,
                'location_id' => $this->resolveLocation($firstRow[self::HEADERS['location']] ?? null)?->id,
                'evening_type_id' => $this->resolveEveningType($firstRow[self::HEADERS['evening_type']] ?? null)?->id,
                'project_id' => $this->resolveProject($firstRow[self::HEADERS['project']] ?? null)?->id,
            ];

            $evening = Evening::find($eveningId);

            if (! $evening) {
                $evening = Evening::forceCreate([
                    'id' => (int) $eveningId,
                    ...$eveningData,
                ]);

                $createdEvenings++;
            } else {
                $evening->update($eveningData);

                $updatedEvenings++;
            }

            $evening->staff()->delete();
            $evening->participants()->delete();
            $evening->expenses()->delete();

            foreach ($rows as $rowData) {
                $recordType = trim($rowData[self::HEADERS['record_type']] ?? '');

                match ($recordType) {
                    'Команда' => $createdStaff += $this->importStaff($evening, $rowData, $skipped, $skippedList),
                    'Игрок' => $createdParticipants += $this->importParticipant($evening, $rowData, $skipped, $skippedList),
                    'Расход' => $createdExpenses += $this->importExpense($evening, $rowData, $skipped, $skippedList),
                    default => $this->skipUnknownType($skipped, $skippedList, $eveningId, $recordType),
                };
            }
        }

        $this->syncEveningsSequence();

        $this->result = [
            'created_evenings' => $createdEvenings,
            'updated_evenings' => $updatedEvenings,
            'imported_evenings' => $createdEvenings + $updatedEvenings,
            'created_staff' => $createdStaff,
            'created_participants' => $createdParticipants,
            'created_expenses' => $createdExpenses,
            'skipped' => $skipped,
            'skipped_list' => $skippedList,
        ];

        Notification::make()
            ->title('Импорт вечеров завершён')
            ->success()
            ->send();

        $this->form->fill();
    }

    private function importStaff(Evening $evening, array $rowData, int &$skipped, array &$skippedList): int
    {
        $hostName = trim($rowData[self::HEADERS['name']] ?? '');

        if ($hostName === '') {
            $skipped++;
            $this->addSkipped($skippedList, (string) $evening->id, 'пустое имя человека в команде');
            return 0;
        }

        $host = Host::where('nickname', $hostName)->first();

        if (! $host) {
            $skipped++;
            $this->addSkipped($skippedList, $hostName, 'человек команды не найден');
            return 0;
        }

        $roleRaw = trim($rowData[self::HEADERS['role']] ?? '');

        $role = match ($roleRaw) {
            'Ведущий' => 'host',
            'Админ' => 'manager',
            default => null,
        };

        if (! $role) {
            $skipped++;
            $this->addSkipped($skippedList, $hostName, "неверная роль команды ({$roleRaw})");
            return 0;
        }

        $evening->staff()->create([
            'host_id' => $host->id,
            'role' => $role,
            'salary' => $this->money($rowData[self::HEADERS['salary']] ?? null),
        ]);

        return 1;
    }

    private function importParticipant(Evening $evening, array $rowData, int &$skipped, array &$skippedList): int
    {
        $playerName = trim($rowData[self::HEADERS['name']] ?? '');

        if ($playerName === '') {
            $skipped++;
            $this->addSkipped($skippedList, (string) $evening->id, 'пустой ник игрока');
            return 0;
        }

        $player = Player::where('nickname', $playerName)->first();

        if (! $player) {
            $skipped++;
            $this->addSkipped($skippedList, $playerName, 'игрок не найден');
            return 0;
        }

        $paymentTypeName = trim($rowData[self::HEADERS['payment_type']] ?? '');

        $paymentType = null;

        if ($paymentTypeName !== '') {
            $paymentType = PaymentType::where('type', $paymentTypeName)->first();

            if (! $paymentType) {
                $skipped++;
                $this->addSkipped($skippedList, $playerName, "тип оплаты не найден ({$paymentTypeName})");
                return 0;
            }
        }

        $evening->participants()->create([
            'player_id' => $player->id,
            'payment_type_id' => $paymentType?->id,
            'paid_amount' => $this->money($rowData[self::HEADERS['paid_amount']] ?? null),
            'is_new_player' => $this->bool($rowData[self::HEADERS['is_new_player']] ?? null),
            'is_full_payment' => $this->bool($rowData[self::HEADERS['is_full_payment']] ?? null),
            'note' => $this->nullableString($rowData[self::HEADERS['note']] ?? null),
        ]);

        return 1;
    }

    private function importExpense(Evening $evening, array $rowData, int &$skipped, array &$skippedList): int
    {
        $categoryName = trim($rowData[self::HEADERS['expense_category']] ?? '');

        if ($categoryName === '') {
            return 0;
        }

        $category = ExpenseCategory::where('name', $categoryName)->first();

        if (! $category) {
            $skipped++;
            $this->addSkipped($skippedList, $categoryName, 'статья расхода не найдена');
            return 0;
        }

        $evening->expenses()->create([
            'expense_category_id' => $category->id,
            'amount' => $this->money($rowData[self::HEADERS['expense_amount']] ?? null),
        ]);

        return 1;
    }

    private function parseDateTime(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formats = [
            'd.m.Y H:i',
            'd.m.Y H:i:s',
            'd.m.Y',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d H:i:s');
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveLocation(?string $name): ?Location
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return Location::firstOrCreate(['name' => $name]);
    }

    private function resolveEveningType(?string $name): ?EveningType
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return EveningType::firstOrCreate(['name' => $name]);
    }

    private function resolveProject(?string $name): ?Project
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return Project::firstOrCreate(['name' => $name]);
    }

    private function syncEveningsSequence(): void
    {
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('evenings', 'id'),
                COALESCE((SELECT MAX(id) FROM evenings), 1)
            )
        ");
    }

    private function addSkipped(array &$skippedList, string $item, string $reason): void
    {
        $skippedList[] = [
            'item' => $item !== '' ? $item : 'unknown',
            'reason' => $reason,
        ];
    }

    private function skipUnknownType(int &$skipped, array &$skippedList, string|int $eveningId, string $recordType): void
    {
        $skipped++;
        $this->addSkipped($skippedList, (string) $eveningId, "неизвестный тип записи ({$recordType})");
    }

    private function bool(?string $value): bool
    {
        $value = mb_strtolower(trim((string) $value));

        return match ($value) {
            'да', 'yes', 'true', '1', 'полная', 'новый' => true,
            default => false,
        };
    }

    private function money(?string $value): float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        return (float) str_replace(',', '.', $value);
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}