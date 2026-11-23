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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use UnitEnum;

class ImportEvenings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

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
        'note' => 'Примечание',
        'address' => 'Адрес',
        'price' => 'Стоимость',
        'venues' => 'Локации и залы',
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
            if (! ctype_digit((string) $eveningId) || (int) $eveningId < 1) {
                $skipped++;
                $this->addSkipped($skippedList, (string) $eveningId, 'неверный ID вечера');

                continue;
            }

            $firstRow = $rows[0];

            $playedAt = $this->parseDateTime($firstRow[self::HEADERS['played_at']] ?? null);

            if (! $playedAt) {
                $skipped++;
                $this->addSkipped($skippedList, (string) $eveningId, 'неверная дата проведения');

                continue;
            }

            $skippedBeforeEvening = $skipped;

            try {
                $imported = DB::transaction(function () use (
                    $eveningId,
                    $firstRow,
                    $playedAt,
                    $rows,
                    $skippedBeforeEvening,
                    &$skipped,
                    &$skippedList,
                ): array {
                    $eveningData = [
                        'played_at' => $playedAt,
                        'evening_type_id' => $this->resolveEveningType(
                            $firstRow[self::HEADERS['evening_type']] ?? null,
                        )?->id,
                        'project_id' => $this->resolveProject(
                            $firstRow[self::HEADERS['project']] ?? null,
                        )?->id,
                    ];

                    if (array_key_exists(self::HEADERS['address'], $firstRow)) {
                        $eveningData['address'] = $this->nullableString($firstRow[self::HEADERS['address']]);
                    }

                    if (array_key_exists(self::HEADERS['price'], $firstRow)) {
                        $eveningData['price'] = $this->schedulePrice($firstRow[self::HEADERS['price']]);
                    }

                    $evening = Evening::query()
                        ->lockForUpdate()
                        ->find((int) $eveningId);
                    $wasCreated = ! $evening;

                    if ($wasCreated) {
                        $evening = Evening::forceCreate([
                            'id' => (int) $eveningId,
                            ...$eveningData,
                        ]);
                    } else {
                        $evening->update($eveningData);
                    }

                    if (array_key_exists(self::HEADERS['venues'], $firstRow)) {
                        $this->importVenues($evening, $firstRow[self::HEADERS['venues']]);
                    }

                    $evening->staff()->delete();
                    $evening->participants()->delete();
                    $evening->expenses()->delete();

                    $eveningStaff = 0;
                    $eveningParticipants = 0;
                    $eveningExpenses = 0;

                    foreach ($rows as $rowData) {
                        $recordType = trim($rowData[self::HEADERS['record_type']] ?? '');

                        match ($recordType) {
                            'Вечер' => 0,
                            'Команда' => $eveningStaff += $this->importStaff(
                                $evening,
                                $rowData,
                                $skipped,
                                $skippedList,
                            ),
                            'Игрок' => $eveningParticipants += $this->importParticipant(
                                $evening,
                                $rowData,
                                $skipped,
                                $skippedList,
                            ),
                            'Расход' => $eveningExpenses += $this->importExpense(
                                $evening,
                                $rowData,
                                $skipped,
                                $skippedList,
                            ),
                            default => $this->skipUnknownType(
                                $skipped,
                                $skippedList,
                                $eveningId,
                                $recordType,
                            ),
                        };
                    }

                    if ($skipped > $skippedBeforeEvening) {
                        throw new \RuntimeException('Вечер содержит некорректные строки.');
                    }

                    return [
                        'created' => $wasCreated,
                        'staff' => $eveningStaff,
                        'participants' => $eveningParticipants,
                        'expenses' => $eveningExpenses,
                    ];
                });

                $createdEvenings += $imported['created'] ? 1 : 0;
                $updatedEvenings += $imported['created'] ? 0 : 1;
                $createdStaff += $imported['staff'];
                $createdParticipants += $imported['participants'];
                $createdExpenses += $imported['expenses'];
            } catch (\Throwable $exception) {
                if ($skipped === $skippedBeforeEvening) {
                    $skipped++;
                    $this->addSkipped(
                        $skippedList,
                        (string) $eveningId,
                        'ошибка импорта — исходные данные вечера сохранены',
                    );

                    report($exception);
                }
            }
        }

        $this->syncEveningsAutoIncrement();

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

    private function importVenues(Evening $evening, ?string $value): void
    {
        $venues = filled($value) ? json_decode($value, true, flags: JSON_THROW_ON_ERROR) : [];
        Validator::make(['venues' => $venues], [
            'venues' => ['present', 'array', 'list'],
            'venues.*.location' => ['required', 'string', 'max:255', 'distinct'],
            'venues.*.address' => ['required', 'string', 'max:500'],
            'venues.*.halls' => ['required', 'array', 'list', 'min:1'],
            'venues.*.halls.*' => ['required', 'string', 'max:255'],
        ])->validate();

        $selections = [];

        foreach ($venues as $venue) {
            $location = Location::firstOrCreate(['name' => trim($venue['location'])], [
                'address' => trim($venue['address']),
            ]);
            $hallIds = [];

            foreach ($venue['halls'] as $name) {
                $hallIds[] = $location->halls()->firstOrCreate(['name' => trim($name)])->id;
            }

            $selections[] = ['location_id' => $location->id, 'hall_ids' => $hallIds];
        }

        $evening->syncVenueSelections($selections);
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
            'Менеджер' => 'manager',
            'Админ' => 'admin',
            'Супервайзер' => 'supervisor',
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

        if ($paymentTypeName === '') {
            $skipped++;
            $this->addSkipped($skippedList, $playerName, 'не указан тип оплаты');

            return 0;
        }

        $paymentType = PaymentType::where('type', $paymentTypeName)->first();

        if (! $paymentType) {
            $skipped++;
            $this->addSkipped($skippedList, $playerName, "тип оплаты не найден ({$paymentTypeName})");

            return 0;
        }

        $participant = $evening->participants()->firstOrCreate(
            ['player_id' => $player->id],
            [
                'payment_type_id' => $paymentType->id,
                'paid_amount' => $this->money($rowData[self::HEADERS['paid_amount']] ?? null),
                'note' => $this->nullableString($rowData[self::HEADERS['note']] ?? null),
            ],
        );

        $participant->payments()->updateOrCreate(
            ['payment_type_id' => $paymentType->id],
            ['amount' => $this->money($rowData[self::HEADERS['paid_amount']] ?? null)],
        );

        return $participant->wasRecentlyCreated ? 1 : 0;
    }

    private function importExpense(Evening $evening, array $rowData, int &$skipped, array &$skippedList): int
    {
        $categoryName = trim($rowData[self::HEADERS['expense_category']] ?? '');

        if ($categoryName === '') {
            $skipped++;
            $this->addSkipped($skippedList, (string) $evening->id, 'не указана статья расхода');

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

    private function resolveEveningType(?string $name): ?EveningType
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return EveningType::firstOrCreate([
            'name' => $name,
        ]);
    }

    private function resolveProject(?string $name): ?Project
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return Project::firstOrCreate([
            'name' => $name,
        ]);
    }

    private function syncEveningsAutoIncrement(): void
    {
        $maxId = (int) DB::table('evenings')->max('id');
        $nextId = $maxId + 1;

        DB::statement("ALTER TABLE evenings AUTO_INCREMENT = {$nextId}");
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

        $this->addSkipped(
            $skippedList,
            (string) $eveningId,
            "неизвестный тип записи ({$recordType})"
        );
    }

    private function schedulePrice(?string $value): ?float
    {
        $value = str_replace(',', '.', trim((string) $value));

        if ($value === '') {
            return null;
        }

        if (! is_numeric($value) || (float) $value < 0 || (float) $value > 99999999.99) {
            throw new \InvalidArgumentException('Некорректная стоимость вечера.');
        }

        return (float) $value;
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
