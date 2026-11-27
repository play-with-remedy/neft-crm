<?php

namespace App\Console\Commands;

use App\Models\AutumnCampaign;
use App\Models\AutumnCase;
use App\Models\EveningParticipant;
use App\Services\SeasonalCaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairAutumnCasesPaymentThreshold extends Command
{
    protected $signature = 'autumn-cases:repair-payment-threshold
        {--campaign= : ID активной осенней кампании}
        {--dry-run : Только показать затронутые записи}
        {--force : Разрешить выполнение в production}';

    protected $description = 'Пересобирает осенние дела с учетом минимальной оплаты 30 BYN';

    public function handle(SeasonalCaseService $service): int
    {
        if (app()->isProduction() && ! $this->option('dry-run') && ! $this->option('force')) {
            $this->error('В production необходимо добавить --force. Сначала рекомендуется выполнить --dry-run.');

            return self::FAILURE;
        }

        $campaigns = AutumnCampaign::query()
            ->where('is_active', true)
            ->when(
                $this->option('campaign'),
                fn ($query, $campaignId) => $query->whereKey($campaignId),
            )
            ->orderBy('starts_at')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->warn('Активные кампании для пересборки не найдены.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $caseIds = AutumnCase::query()
                ->where('autumn_campaign_id', $campaign->getKey())
                ->pluck('id');
            $linkedProgress = EveningParticipant::query()
                ->whereIn('autumn_case_id', $caseIds)
                ->where('is_autumn_reward', false);
            $invalidProgressCount = (clone $linkedProgress)
                ->where('paid_amount', '<', $campaign->minimum_qualifying_payment)
                ->count();
            $linkedCount = EveningParticipant::query()
                ->whereIn('autumn_case_id', $caseIds)
                ->count();

            $this->newLine();
            $this->info("Кампания #{$campaign->getKey()}: {$campaign->name}");
            $this->line("Дел до пересборки: {$caseIds->count()}");
            $this->line("Связанных посещений: {$linkedCount}");
            $this->line("Посещений с оплатой ниже {$campaign->minimum_qualifying_payment} BYN в прогрессе: {$invalidProgressCount}");

            if ($this->option('dry-run')) {
                continue;
            }

            $result = DB::transaction(function () use ($campaign, $service): array {
                $participations = EveningParticipant::query()
                    ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
                    ->whereBetween('evenings.played_at', [
                        $campaign->starts_at->copy()->startOfDay(),
                        $campaign->ends_at->copy()->endOfDay(),
                    ])
                    ->get(['evening_participants.id', 'evening_participants.player_id']);
                $playerIds = $participations->pluck('player_id')
                    ->merge($campaign->cases()->pluck('player_id'))->unique()->sort();

                foreach ($playerIds as $playerId) {
                    $service->recalculatePlayerCampaign((int) $playerId, $campaign->id);
                }

                return [
                    'participations' => $participations->count(),
                    'cases' => AutumnCase::query()
                        ->where('autumn_campaign_id', $campaign->getKey())
                        ->count(),
                ];
            });

            $this->line("Проверено посещений кампании: {$result['participations']}");
            $this->info("Дел после пересборки: {$result['cases']}");
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Предварительная проверка завершена, данные не изменялись.');
        }

        return self::SUCCESS;
    }
}
