<?php

namespace Database\Seeders;

use App\Models\AutumnCampaign;
use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Models\EveningType;
use App\Models\PaymentType;
use App\Models\Player;
use App\Models\Project;
use App\Services\SeasonalCaseService;
use Illuminate\Database\Seeder;
use RuntimeException;

class SeasonalCasesDemoSeeder extends Seeder
{
    public const PROJECT_NAME = '[ТЕСТ] Сезонные дела';

    public function run(): void
    {
        $connection = config('database.connections.'.config('database.default'));

        if (! app()->environment('local', 'testing')
            || ($connection['driver'] !== 'sqlite' && ! in_array($connection['host'], ['localhost', '127.0.0.1', '::1'], true))) {
            throw new RuntimeException('Демонстрационные данные разрешены только в локальной базе.');
        }

        $campaigns = AutumnCampaign::query()->whereIn('starts_at', ['2026-09-01', '2026-12-01'])->orderBy('starts_at')->get();

        if ($campaigns->count() !== 2) {
            throw new RuntimeException('Сначала примените миграции осенней и зимней кампаний.');
        }

        app(SeasonalCaseService::class)->batch(function () use ($campaigns): void {
            $project = Project::firstOrCreate(['name' => self::PROJECT_NAME]);
            $type = EveningType::firstOrCreate(['name' => '[ТЕСТ] Проверка сезонов']);
            $cash = PaymentType::firstOrCreate(['type' => 'Наличные']);
            $card = PaymentType::firstOrCreate(['type' => 'Карта']);
            $free = PaymentType::firstOrCreate(['type' => 'Бесплатно']);

            foreach ($campaigns as $campaign) {
                $evenings = [];
                $monthDays = [
                    [1, 3, 5, 7, 9, 11, 13, 15, 17, 19],
                    [1, 4, 7, 10, 13, 16, 19, 22, 25, 28],
                    $campaign->ends_at->month === 11
                        ? [3, 6, 9, 12, 15, 18, 21, 24, 27, 30]
                        : [1, 4, 7, 10, 13, 16, 19, 22, 25, 28],
                ];

                foreach ($monthDays as $month => $days) {
                    foreach ($days as $day) {
                        $date = $campaign->starts_at->toImmutable()->addMonthsNoOverflow($month)->day($day)->setTime(19, 0);
                        $evenings[] = Evening::firstOrCreate([
                            'project_id' => $project->id,
                            'played_at' => $date->format('Y-m-d H:i:s'),
                        ], ['evening_type_id' => $type->id, 'price' => 30]);
                    }
                }

                foreach ($this->scenarios($campaign->required_visits) as $scenario => $amounts) {
                    $player = Player::firstOrCreate(['nickname' => '[ТЕСТ] '.$scenario], [
                        'notes' => 'Демонстрационный игрок для проверки сезонных дел. Сценарий: '.$scenario.'.',
                    ]);

                    foreach ($amounts as $index => $amount) {
                        $note = '[ТЕСТ] '.$campaign->name.'. '.$scenario.'.';

                        if ($scenario === 'Исправление оплаты' && $index === $campaign->required_visits) {
                            $note .= ' Здесь должна быть бесплатная игра: замените оплату 30 на 0 и проверьте пересчёт следующего посещения.';
                        }

                        $participant = EveningParticipant::firstOrCreate([
                            'evening_id' => $evenings[$index]->id,
                            'player_id' => $player->id,
                        ], ['note' => $note]);

                        // A second run preserves any corrections made during manual testing.
                        if (! $participant->wasRecentlyCreated) {
                            continue;
                        }

                        $payments = $amount === 0
                            ? [[$free->id, 0]]
                            : ($scenario === 'Раздельная оплата' ? [[$cash->id, 20], [$card->id, 10]] : [[$cash->id, $amount]]);

                        foreach ($payments as [$paymentTypeId, $paymentAmount]) {
                            $participant->payments()->create(['payment_type_id' => $paymentTypeId, 'amount' => $paymentAmount]);
                        }
                    }
                }
            }
        });

        $this->command?->info('Готово: по 30 тестовых вечеров на сезон и 8 игроков со сценариями. Проект: '.self::PROJECT_NAME);
    }

    /** @return array<string, array<int, int>> */
    private function scenarios(int $requiredVisits): array
    {
        $regular = [];
        $split = [];
        $small = [];

        foreach (range(0, 29) as $index) {
            $regular[$index] = ($index + 1) % ($requiredVisits + 1) === 0 ? 0 : 30;
            $small[$index] = $index % 2 === 0 ? 15 : 29;

            if ($index < 10) {
                $split[$index] = $regular[$index];
            }
        }

        return [
            'Регулярные посещения' => $regular,
            'Награда доступна' => array_fill_keys(range(22, 21 + $requiredVisits), 30),
            'Незавершённое дело' => array_fill_keys([26, 27, 28], 30),
            'Пропуск срока' => array_fill_keys([0, 1, 12, 13], 30),
            'Малая оплата' => $small,
            'Исправление оплаты' => array_fill_keys(range(0, $requiredVisits + 1), 30),
            'Раздельная оплата' => $split,
            'Нулевые визиты' => [0 => 0, 1 => 0] + array_fill_keys(range(2, $requiredVisits + 1), 30)
                + [$requiredVisits + 2 => 0],
        ];
    }
}
