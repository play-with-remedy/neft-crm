<?php

namespace App\Filament\Pages;

use App\Models\Host;
use App\Models\Player;
use App\Models\Source;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ImportPlayers extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Импорт игроков';

    protected static ?string $title = 'Импорт игроков';

    protected static UnitEnum|string|null $navigationGroup = 'Синхронизация';

    protected string $view = 'filament.pages.import-players';

    public ?array $data = [];

    public ?array $result = null;

    private const DEFAULT_BIRTHDAY = '01.01.1900';

    private const HEADERS = [
        'nickname' => 'Игровой ник',
        'first_name' => 'Имя',
        'last_name' => 'Фамилия',
        'gender' => 'Пол',
        'birthday' => 'Дата рождения',
        'phone' => 'Телефон',
        'telegram' => 'Telegram',
        'first_visit_at' => 'Дата первого посещения',
        'source' => 'Источник',
        'first_host' => 'Ведущий',
    ];

    private const REQUIRED_HEADERS = [
        'nickname',
        'first_name',
        'gender',
        'birthday',
        'first_visit_at',
    ];

    public function mount(): void
    {
        $this->form->fill([
            'update_existing' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\FileUpload::make('file')
                    ->label('CSV файл с игроками')
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/vnd.ms-excel',
                    ])
                    ->required(),

                Forms\Components\Toggle::make('update_existing')
                    ->label('Обновлять пустые поля у существующих игроков')
                    ->default(true),
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
            $header = trim($header, "\"' \t\n\r\0\x0B");

            return $header;
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

        $created = 0;
        $createdWithChangedNickname = 0;
        $updatedPlayers = 0;
        $skipped = 0;
        $emptyBirthdayUsed = 0;

        $createdWithChangedNicknameList = [];
        $updatedPlayersList = [];
        $skippedList = [];
        $emptyBirthdayUsedList = [];

        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            if (count($row) !== count($headers)) {
                $skipped++;
                $this->addSkipped($skippedList, '', 'неверное количество колонок');
                continue;
            }

            $rowData = array_combine($headers, $row);

            $nickname = trim($rowData[self::HEADERS['nickname']] ?? '');
            $firstName = trim($rowData[self::HEADERS['first_name']] ?? '');
            $lastName = trim($rowData[self::HEADERS['last_name']] ?? '');
            $genderRaw = trim($rowData[self::HEADERS['gender']] ?? '');
            $gender = $this->normalizeGender($genderRaw);
            $birthday = trim($rowData[self::HEADERS['birthday']] ?? '');
            $phone = $this->normalizePhone($rowData[self::HEADERS['phone']] ?? null);
            $telegram = $this->normalizeTelegram($rowData[self::HEADERS['telegram']] ?? null);
            $firstVisitAt = trim($rowData[self::HEADERS['first_visit_at']] ?? '');

            $sourceName = trim($rowData[self::HEADERS['source']] ?? '');
            $source = $this->resolveSource($sourceName);

            $firstHostName = trim($rowData[self::HEADERS['first_host']] ?? '');
            $firstHost = $this->resolveHost($firstHostName);

            if ($nickname === '') {
                $skipped++;
                $this->addSkipped($skippedList, '', 'пустой игровой ник');
                continue;
            }

            if ($firstName === '') {
                $skipped++;
                $this->addSkipped($skippedList, $nickname, 'не указано имя');
                continue;
            }

            if ($gender === null) {
                $skipped++;
                $this->addSkipped($skippedList, $nickname, "неверный пол ({$genderRaw})");
                continue;
            }

            if ($birthday === '') {
                $birthday = self::DEFAULT_BIRTHDAY;
                $emptyBirthdayUsed++;
                $emptyBirthdayUsedList[] = $nickname;
            }

            if ($firstVisitAt === '') {
                $skipped++;
                $this->addSkipped($skippedList, $nickname, 'не указана дата первого посещения');
                continue;
            }

            $birthdayParts = $this->parseBirthday($birthday);

            if ($birthdayParts === null) {
                $skipped++;
                $this->addSkipped($skippedList, $nickname, 'неверная дата рождения');
                continue;
            }

            try {
                $firstVisitDate = Carbon::createFromFormat('d.m.Y', $firstVisitAt);

                if ($firstVisitDate->format('d.m.Y') !== $firstVisitAt) {
                    throw new \RuntimeException('Invalid date');
                }
            } catch (\Throwable) {
                $skipped++;
                $this->addSkipped($skippedList, $nickname, 'неверная дата первого посещения');
                continue;
            }

            $playerData = [
                'nickname' => $nickname,
                'first_name' => $firstName,
                'last_name' => $lastName !== '' ? $lastName : null,
                'gender' => $gender,
                'birth_day' => $birthdayParts['day'],
                'birth_month' => $birthdayParts['month'],
                'birth_year' => $birthdayParts['year'],
                'phone' => $phone,
                'telegram' => $telegram,
                'first_visit_at' => $firstVisitDate->format('d.m.Y'),
                'source_id' => $source?->id,
                'first_host_id' => $firstHost?->id,
            ];

            $player = Player::where('nickname', $nickname)->first();

            if (! $player) {
                Player::create($playerData);

                $created++;

                continue;
            }

            if ($this->hasSameBirthday($player, $birthdayParts)) {
                if (! ($data['update_existing'] ?? false)) {
                    $skipped++;
                    $this->addSkipped($skippedList, $nickname, 'игрок уже существует, обновление выключено');
                    continue;
                }

                $fieldsToUpdate = $this->onlyEmptyFields($player, $playerData);

                if ($fieldsToUpdate === []) {
                    $skipped++;
                    $this->addSkipped($skippedList, $nickname, 'нет пустых полей для обновления');
                    continue;
                }

                $player->update($fieldsToUpdate);

                $updatedPlayers++;
                $updatedPlayersList[] = $nickname;

                continue;
            }

            $newNickname = $this->makeNicknameWithBirthday($nickname, $birthdayParts);

            if (Player::where('nickname', $newNickname)->exists()) {
                $skipped++;
                $this->addSkipped($skippedList, $nickname, "сгенерированный ник {$newNickname} уже существует");
                continue;
            }

            $playerData['nickname'] = $newNickname;

            Player::create($playerData);

            $created++;
            $createdWithChangedNickname++;
            $createdWithChangedNicknameList[] = $newNickname;
        }

        fclose($file);

        $this->result = [
            'created' => $created,

            'created_with_changed_nickname' => $createdWithChangedNickname,
            'created_with_changed_nickname_list' => $createdWithChangedNicknameList,

            'updated_players' => $updatedPlayers,
            'updated_players_list' => $updatedPlayersList,

            'skipped' => $skipped,
            'skipped_list' => $skippedList,

            'empty_birthday_used' => $emptyBirthdayUsed,
            'empty_birthday_used_list' => $emptyBirthdayUsedList,
        ];

        Notification::make()
            ->title('Импорт завершен')
            ->success()
            ->send();

        $this->form->fill([
            'update_existing' => true,
        ]);
    }

    private function addSkipped(array &$skippedList, string $nickname, string $reason): void
    {
        $skippedList[] = [
            'nickname' => $nickname !== '' ? $nickname : 'unknown',
            'reason' => $reason,
        ];
    }

    private function parseBirthday(string $birthday): ?array
    {
        $birthday = trim($birthday);

        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $birthday, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if (! checkdate($month, $day, $year)) {
                return null;
            }

            return [
                'day' => $day,
                'month' => $month,
                'year' => $year,
            ];
        }

        if (preg_match('/^(\d{2})\.(\d{2})$/', $birthday, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];

            if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
                return null;
            }

            return [
                'day' => $day,
                'month' => $month,
                'year' => null,
            ];
        }

        return null;
    }

    private function hasSameBirthday(Player $player, array $birthdayParts): bool
    {
        return (int) $player->birth_day === $birthdayParts['day']
            && (int) $player->birth_month === $birthdayParts['month']
            && (string) $player->birth_year === (string) $birthdayParts['year'];
    }

    private function onlyEmptyFields(Player $player, array $playerData): array
    {
        unset(
            $playerData['nickname'],
            $playerData['birth_day'],
            $playerData['birth_month'],
            $playerData['birth_year'],
        );

        $fieldsToUpdate = [];

        foreach ($playerData as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($player->{$field} === null || $player->{$field} === '') {
                $fieldsToUpdate[$field] = $value;
            }
        }

        return $fieldsToUpdate;
    }

    private function makeNicknameWithBirthday(string $nickname, array $birthdayParts): string
    {
        return sprintf(
            '%s%02d%02d%s',
            $nickname,
            $birthdayParts['day'],
            $birthdayParts['month'],
            $birthdayParts['year'] ?? '0000',
        );
    }

    private function normalizeGender(?string $value): ?string
    {
        $value = trim((string) $value);

        return match ($value) {
            'м' => 'male',
            'ж' => 'female',
            default => null,
        };
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);

        return $phone !== '' ? $phone : null;
    }

    private function normalizeTelegram(?string $telegram): ?string
    {
        $telegram = trim((string) $telegram);

        if ($telegram === '') {
            return null;
        }

        return ltrim($telegram, '@');
    }

    private function resolveSource(?string $sourceName): ?Source
    {
        $sourceName = trim((string) $sourceName);

        if ($sourceName === '') {
            return null;
        }

        $source = Source::where('name', $sourceName)->first();

        if ($source) {
            return $source;
        }

        return Source::where('name', 'Другое')->first();
    }

    private function resolveHost(?string $hostName): ?Host
    {
        $hostName = trim((string) $hostName);

        if ($hostName === '') {
            return null;
        }

        return Host::where('nickname', $hostName)->first();
    }
}