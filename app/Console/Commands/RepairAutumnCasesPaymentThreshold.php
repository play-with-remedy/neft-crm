<?php

namespace App\Console\Commands;

use App\Models\AutumnCampaign;
use App\Models\AutumnCase;
use App\Models\EveningParticipant;
use App\Services\AutumnCaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairAutumnCasesPaymentThreshold extends Command
{
    protected $signature = 'autumn-cases:repair-payment-threshold
        {--campaign= : ID активной осенней кампании}
        {--dry-run : Только показать затронутые записи}
        {--force : Разрешить выполнение в production}';

    protected $description = 'Пересобирает осенние дела с учетом минимальной оплаты 30 BYN';

    public function handle(AutumnCaseService $service): int
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
                ->where('paid_amount', '<', AutumnCaseService::MINIMUM_QUALIFYING_PAYMENT)
                ->count();
            $linkedCount = EveningParticipant::query()
                ->whereIn('autumn_case_id', $caseIds)
                ->count();

            $this->newLine();
            $this->info("Кампания #{$campaign->getKey()}: {$campaign->name}");
            $this->line("Дел до пересборки: {$caseIds->count()}");
            $this->line("Связанных посещений: {$linkedCount}");
            $this->line("Посещений с оплатой ниже 30 BYN в прогрессе: {$invalidProgressCount}");

            if ($this->option('dry-run')) {
                continue;
            }

            $result = DB::transaction(function () use ($campaign, $caseIds, $service): array {
                AutumnCampaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();

                $participationIds = EveningParticipant::query()
                    ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
                    ->whereBetween('evenings.played_at', [
                        $campaign->starts_at->copy()->startOfDay(),
                        $campaign->ends_at->copy()->endOfDay(),
                    ])
                    ->orderBy('evenings.played_at')
                    ->orderBy('evening_participants.id')
                    ->pluck('evening_participants.id');

                EveningParticipant::query()
                    ->whereIn('autumn_case_id', $caseIds)
                    ->update([
                        'autumn_case_id' => null,
                        'autumn_case_visit_number' => null,
                        'is_autumn_reward' => false,
                    ]);

                AutumnCase::query()
                    ->where('autumn_campaign_id', $campaign->getKey())
                    ->delete();

                foreach ($participationIds as $participationId) {
                    $service->processParticipation(
                        EveningParticipant::query()->findOrFail($participationId),
                    );
                }

                return [
                    'participations' => $participationIds->count(),
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
