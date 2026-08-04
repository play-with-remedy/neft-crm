<?php

namespace App\Filament\Pages;

use App\Models\Player;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class ImportNicknameList extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = 'Импорт списка ников';

    protected static ?string $title = 'Импорт списка ников';

    protected static UnitEnum | string | null $navigationGroup = 'Синхронизация';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.import-nickname-list';

    public ?array $data = [];

    public ?array $preview = null;

    public array $decisions = [];

    public ?array $result = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('nicknames')
                    ->label('Ники игроков')
                    ->helperText('По одному нику в строке. Также можно разделять ники запятыми или точками с запятой.')
                    ->rows(16)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function previewNicknames(): void
    {
        $data = $this->form->getState();
        $nicknames = $this->parseNicknames((string) ($data['nicknames'] ?? ''));

        if ($nicknames === []) {
            Notification::make()
                ->title('Добавьте хотя бы один ник')
                ->warning()
                ->send();

            return;
        }

        $players = Player::query()
            ->orderBy('nickname')
            ->get(['id', 'nickname']);

        $existingByNickname = $players->keyBy(
            fn (Player $player): string => $this->normalizeNickname($player->nickname)
        );

        $exact = [];
        $similar = [];
        $new = [];
        $duplicates = [];
        $seen = [];

        foreach ($nicknames as $nickname) {
            $normalized = $this->normalizeNickname($nickname);

            if (isset($seen[$normalized])) {
                $duplicates[] = $nickname;
                continue;
            }

            $seen[$normalized] = true;

            if ($player = $existingByNickname->get($normalized)) {
                $exact[] = [
                    'nickname' => $nickname,
                    'existing' => $player->nickname,
                ];
                continue;
            }

            $candidates = $players
                ->map(function (Player $player) use ($normalized): array {
                    $candidate = $this->normalizeNickname($player->nickname);

                    return [
                        'id' => $player->id,
                        'nickname' => $player->nickname,
                        'distance' => $this->nicknameDistance($normalized, $candidate),
                        'max_length' => max($this->nicknameLength($normalized), $this->nicknameLength($candidate)),
                    ];
                })
                ->filter(fn (array $candidate): bool => $this->isSimilar($candidate))
                ->sortBy('distance')
                ->take(3)
                ->values()
                ->map(fn (array $candidate): array => [
                    'id' => $candidate['id'],
                    'nickname' => $candidate['nickname'],
                ])
                ->all();

            if ($candidates !== []) {
                $index = count($similar);
                $similar[] = [
                    'nickname' => $nickname,
                    'candidates' => $candidates,
                ];
                $this->decisions[$index] = 'match:' . $candidates[0]['id'];
                continue;
            }

            $new[] = $nickname;
        }

        $this->preview = compact('exact', 'similar', 'new', 'duplicates');
        $this->result = null;
    }

    public function importNicknames(): void
    {
        if ($this->preview === null) {
            return;
        }

        $toCreate = $this->preview['new'];
        $matched = count($this->preview['exact']);
        $skipped = count($this->preview['duplicates']);

        foreach ($this->preview['similar'] as $index => $item) {
            $decision = $this->decisions[$index] ?? 'skip';

            if ($decision === 'create') {
                $toCreate[] = $item['nickname'];
            } elseif (str_starts_with($decision, 'match:')) {
                $matched++;
            } else {
                $skipped++;
            }
        }

        $created = [];
        $alreadyExists = [];

        DB::transaction(function () use ($toCreate, &$created, &$alreadyExists): void {
            $existing = Player::query()
                ->get(['nickname'])
                ->mapWithKeys(fn (Player $player): array => [
                    $this->normalizeNickname($player->nickname) => true,
                ])
                ->all();

            foreach ($toCreate as $nickname) {
                $normalized = $this->normalizeNickname($nickname);

                if (isset($existing[$normalized])) {
                    $alreadyExists[] = $nickname;
                    continue;
                }

                Player::create([
                    'nickname' => $nickname,
                    'first_name' => null,
                    'gender' => null,
                    'birth_day' => null,
                    'birth_month' => null,
                    'notes' => 'Создан из списка ников. Данные игрока требуют заполнения.',
                ]);

                $existing[$normalized] = true;
                $created[] = $nickname;
            }
        });

        $this->result = [
            'created' => $created,
            'matched' => $matched + count($alreadyExists),
            'skipped' => $skipped,
        ];

        $this->preview = null;
        $this->decisions = [];

        Notification::make()
            ->title('Импорт завершён')
            ->body('Создано игроков: ' . count($created))
            ->success()
            ->send();
    }

    private function parseNicknames(string $value): array
    {
        return collect(preg_split('/[\r\n,;]+/u', $value) ?: [])
            ->map(fn (string $nickname): string => preg_replace('/\s+/u', ' ', trim($nickname)) ?? '')
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeNickname(string $nickname): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($nickname)) ?? '');
    }

    private function nicknameLength(string $nickname): int
    {
        return mb_strlen($nickname);
    }

    private function nicknameDistance(string $first, string $second): int
    {
        $firstChars = preg_split('//u', $first, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $secondChars = preg_split('//u', $second, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $previous = range(0, count($secondChars));

        foreach ($firstChars as $firstIndex => $firstChar) {
            $current = [$firstIndex + 1];

            foreach ($secondChars as $secondIndex => $secondChar) {
                $current[] = min(
                    $current[$secondIndex] + 1,
                    $previous[$secondIndex + 1] + 1,
                    $previous[$secondIndex] + ($firstChar === $secondChar ? 0 : 1),
                );
            }

            $previous = $current;
        }

        return $previous[count($secondChars)] ?? count($firstChars);
    }

    private function isSimilar(array $candidate): bool
    {
        $allowedDistance = match (true) {
            $candidate['max_length'] <= 4 => 1,
            $candidate['max_length'] <= 8 => 2,
            default => 3,
        };

        return $candidate['distance'] > 0
            && $candidate['distance'] <= $allowedDistance;
    }
}
