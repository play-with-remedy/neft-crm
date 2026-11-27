<?php

namespace Tests\Feature;

use App\Enums\AutumnCaseStatus;
use App\Enums\UserRole;
use App\Filament\Pages\AutumnCampaignDashboard;
use App\Filament\Resources\Evenings\Pages\EditEvening;
use App\Filament\Resources\Players\Pages\PlayerAutumnCase;
use App\Models\AutumnCampaign;
use App\Models\AutumnCase;
use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Models\PaymentType;
use App\Models\Player;
use App\Models\User;
use App\Services\SeasonalCaseService;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SeasonalCaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutumnCampaign $winter;

    private Player $player;

    private PaymentType $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2027, 1, 10));
        $this->beforeApplicationDestroyed(Repeater::fake());
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $this->winter = AutumnCampaign::where('starts_at', '2026-12-01')->sole();
        $this->player = Player::create(['nickname' => 'Зимний игрок']);
        $this->cash = PaymentType::create(['type' => 'Наличные']);
    }

    public function test_seasons_have_separate_rules_and_winter_continues_across_new_year(): void
    {
        $autumn = AutumnCampaign::where('starts_at', '2026-09-01')->sole();
        $this->assertSame(5, $autumn->required_visits);
        $this->assertFalse($autumn->reward_requires_zero_payment);
        $this->assertSame(6, $this->winter->required_visits);
        $this->assertTrue($this->winter->reward_requires_zero_payment);
        $this->assertSame('2027-02-28', $this->winter->ends_at->toDateString());

        foreach (['2026-12-20', '2026-12-22', '2026-12-24', '2026-12-26', '2026-12-28'] as $date) {
            $this->visit($date);
        }

        $case = $this->winter->cases()->sole();
        $this->assertNull($case->qualified_at);
        $this->visit('2026-12-30');
        $this->assertSame(AutumnCaseStatus::RewardAvailable, $case->fresh()->status);

        $reward = $this->visit('2027-01-02', 0);
        $next = $this->visit('2027-01-03');
        $this->assertTrue($reward->fresh()->is_autumn_reward);
        $this->assertSame(AutumnCaseStatus::Completed, $case->fresh()->status);
        $this->assertSame(2, $next->fresh()->autumnCase->number);
        $this->assertSame(1, $next->fresh()->autumn_case_visit_number);
    }

    public function test_migration_keeps_existing_autumn_cases_and_links_intact(): void
    {
        $visit = $this->visit('2026-11-20')->fresh();
        $caseSnapshot = $visit->autumnCase->getAttributes();
        $visitSnapshot = $visit->getAttributes();
        $migration = require database_path('migrations/2026_11_26_120000_add_seasonal_campaign_rules.php');

        $migration->down();
        $migration->up();

        $this->assertSame($caseSnapshot, $visit->fresh()->autumnCase->getAttributes());
        $this->assertSame($visitSnapshot, $visit->fresh()->getAttributes());
        $this->assertSame(5, $visit->fresh()->autumnCase->campaign->required_visits);
        $this->assertSame(6, $this->winter->fresh()->required_visits);
        $this->assertSame(2, AutumnCampaign::count());
    }

    public function test_default_season_changes_on_december_first(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 30));
        $this->assertSame(AutumnCampaign::where('starts_at', '2026-09-01')->sole()->id, AutumnCampaign::defaultId());
        $this->travelTo(now()->setDate(2026, 12, 1));
        $this->assertSame($this->winter->id, AutumnCampaign::defaultId());
    }

    public function test_season_and_reward_expiry_use_minsk_date_at_midnight(): void
    {
        $this->qualify();
        $case = $this->winter->cases()->sole();
        $this->travelTo(Carbon::parse('2027-02-28 20:59:00', 'UTC'));
        $this->assertSame(AutumnCaseStatus::RewardAvailable, $case->fresh()->status);
        $this->travelTo(Carbon::parse('2027-02-28 21:00:00', 'UTC'));
        $this->assertSame(AutumnCaseStatus::RewardExpired, $case->fresh()->status);

        $this->travelTo(Carbon::parse('2026-11-30 21:00:00', 'UTC'));
        $this->assertSame($this->winter->id, AutumnCampaign::defaultId());
    }

    public function test_notes_and_payment_method_changes_do_not_recalculate_cases(): void
    {
        $visit = $this->visit('2026-12-01')->fresh();
        $card = PaymentType::create(['type' => 'Карта']);
        $service = Mockery::mock(SeasonalCaseService::class)->makePartial();
        $service->shouldNotReceive('recalculatePlayerCampaign');
        $this->instance(SeasonalCaseService::class, $service);

        $visit->update(['note' => 'Уточнение']);
        $visit->payments()->sole()->update(['payment_type_id' => $card->id]);

        $this->assertSame($card->id, $visit->fresh()->payment_type_id);
        $this->assertSame(1, $visit->fresh()->autumn_case_visit_number);
    }

    public function test_paid_visit_does_not_spend_reward_and_correcting_it_updates_following_case(): void
    {
        $this->qualify();
        $paid = $this->visit('2026-12-07');
        $following = $this->visit('2026-12-08');
        $case = $this->winter->cases()->sole();

        $this->assertNull($paid->fresh()->autumn_case_id);
        $this->assertNull($following->fresh()->autumn_case_id);
        $this->assertNull($case->completed_at);

        $paid->payments()->sole()->update(['amount' => 0]);

        $this->assertTrue($paid->fresh()->is_autumn_reward);
        $this->assertSame('2026-12-07', $case->fresh()->completed_at->toDateString());
        $this->assertSame(2, $following->fresh()->autumnCase->number);
        $this->assertSame(1, $following->fresh()->autumn_case_visit_number);

        $paid->payments()->sole()->update(['amount' => 30]);
        $this->assertNull($case->fresh()->completed_at);
        $this->assertFalse($paid->fresh()->is_autumn_reward);
        $this->assertNull($following->fresh()->autumn_case_id);
        $this->assertSame(1, $this->winter->cases()->count());
    }

    public function test_correcting_earlier_payment_rebuilds_the_entire_chain_without_touching_autumn_or_other_players(): void
    {
        $autumnVisit = $this->visit('2026-11-20');
        $autumnSnapshot = $autumnVisit->fresh()->autumnCase->getAttributes();
        $otherPlayer = Player::create(['nickname' => 'Другой игрок']);
        $otherVisit = $this->visit('2026-12-01', player: $otherPlayer);
        $otherSnapshot = $otherVisit->fresh()->autumnCase->getAttributes();
        $visits = $this->qualify();
        $reward = $this->visit('2026-12-07', 0);
        $next = $this->visit('2026-12-08');
        $laterFree = $this->visit('2026-12-09', 0);
        $caseId = $reward->fresh()->autumn_case_id;

        $visits[2]->payments()->sole()->update(['amount' => 0]);

        $this->assertNull($reward->fresh()->autumn_case_id);
        $this->assertSame(6, $next->fresh()->autumn_case_visit_number);
        $this->assertSame($caseId, $next->fresh()->autumn_case_id);
        $this->assertTrue($laterFree->fresh()->is_autumn_reward);
        $this->assertSame('2026-12-09', AutumnCase::findOrFail($caseId)->completed_at->toDateString());
        $this->assertSame($autumnSnapshot, $autumnVisit->fresh()->autumnCase->getAttributes());
        $this->assertSame($otherSnapshot, $otherVisit->fresh()->autumnCase->getAttributes());
    }

    public function test_deleting_payment_and_participant_updates_progress_and_reward(): void
    {
        $visits = $this->qualify();
        $reward = $this->visit('2026-12-07', 0);
        $case = $this->winter->cases()->sole();

        $visits[0]->payments()->sole()->delete();
        $this->assertSame('0.00', $visits[0]->fresh()->paid_amount);
        $this->assertSame(5, $case->fresh()->progress);
        $this->assertNull($case->fresh()->qualified_at);
        $this->assertNull($case->fresh()->completed_at);
        $this->assertNull($reward->fresh()->autumn_case_id);

        $visits[1]->delete();
        $this->assertSame(4, $case->fresh()->progress);
        $this->assertSame(1, $visits[2]->fresh()->autumn_case_visit_number);
    }

    public function test_deleting_evening_cascade_rebuilds_remaining_visits(): void
    {
        $first = $this->visit('2026-12-01');
        $second = $this->visit('2026-12-02');
        $case = $this->winter->cases()->sole();

        Livewire::test(EditEvening::class, ['record' => $first->evening_id])
            ->callAction('delete')->assertHasNoActionErrors();

        $this->assertNull($first->fresh());
        $this->assertSame(1, $second->fresh()->autumn_case_visit_number);
        $this->assertSame('2026-12-02', $case->fresh()->started_at->toDateString());
    }

    public function test_moving_evening_between_seasons_clears_old_case_and_uses_destination_rules(): void
    {
        $visit = $this->visit('2026-11-28');
        $oldCaseId = $visit->fresh()->autumn_case_id;

        $visit->evening->update(['played_at' => '2026-12-02 19:00:00']);

        $this->assertNull(AutumnCase::find($oldCaseId));
        $this->assertSame($this->winter->id, $visit->fresh()->autumnCase->autumn_campaign_id);

        $visit->evening->update(['played_at' => '2027-03-01 19:00:00']);
        $this->assertNull($visit->fresh()->autumn_case_id);
        $this->assertSame(0, $this->winter->cases()->count());
    }

    public function test_backdated_visits_are_sorted_and_changing_player_updates_both_histories(): void
    {
        $last = $this->visit('2026-12-03');
        $first = $this->visit('2026-12-01');
        $middle = $this->visit('2026-12-02');
        $this->assertSame(1, $first->fresh()->autumn_case_visit_number);
        $this->assertSame(2, $middle->fresh()->autumn_case_visit_number);
        $this->assertSame(3, $last->fresh()->autumn_case_visit_number);

        $other = Player::create(['nickname' => 'Новый игрок']);
        $first->update(['player_id' => $other->id]);

        $this->assertSame($other->id, $first->fresh()->autumnCase->player_id);
        $this->assertSame(1, $middle->fresh()->autumn_case_visit_number);
        $this->assertSame('2026-12-02', $middle->fresh()->autumnCase->started_at->toDateString());
    }

    public function test_split_payment_total_can_both_qualify_and_unqualify_visit(): void
    {
        $visit = $this->visit('2026-12-01', 10);
        $card = PaymentType::create(['type' => 'Карта']);
        $payment = $visit->payments()->create(['payment_type_id' => $card->id, 'amount' => 20]);
        $this->assertSame(1, $visit->fresh()->autumn_case_visit_number);

        $payment->update(['amount' => 19]);

        $this->assertSame('29.00', $visit->fresh()->paid_amount);
        $this->assertNull($visit->fresh()->autumn_case_id);
        $this->assertSame(0, $this->winter->cases()->count());
    }

    public function test_form_save_batches_payment_changes_into_one_player_recalculation(): void
    {
        $visits = $this->qualify();
        $reward = $this->visit('2026-12-07');
        $service = Mockery::mock(SeasonalCaseService::class)->makePartial();
        $service->shouldReceive('recalculatePlayerCampaign')->once()->with($this->player->id, $this->winter->id)->passthru();
        $this->instance(SeasonalCaseService::class, $service);

        $component = Livewire::test(EditEvening::class, ['record' => $reward->evening_id]);
        $participants = $component->get('data.participants');
        $participantKey = array_key_first($participants);
        $paymentKey = array_key_first($participants[$participantKey]['payments']);

        $component->fillForm([
            "participants.{$participantKey}.payments.{$paymentKey}.amount" => 0,
        ])->call('save')->assertHasNoFormErrors();

        $this->assertTrue($reward->fresh()->is_autumn_reward);
        $this->assertSame(6, $visits[5]->fresh()->autumn_case_visit_number);
    }

    public function test_batch_rolls_back_payments_and_results_together_and_does_not_keep_pending_work(): void
    {
        $visit = $this->visit('2026-12-01');
        $caseId = $visit->fresh()->autumn_case_id;
        $service = app(SeasonalCaseService::class);

        try {
            $service->batch(function () use ($visit): void {
                $visit->payments()->sole()->update(['amount' => 0]);
                throw new RuntimeException('Cancel save');
            });
            $this->fail('The save should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cancel save', $exception->getMessage());
        }

        $this->assertSame('30.00', $visit->fresh()->paid_amount);
        $this->assertSame($caseId, $visit->fresh()->autumn_case_id);
        $visit->payments()->sole()->update(['amount' => 0]);
        $this->assertNull($visit->fresh()->autumn_case_id);
    }

    public function test_recalculating_unchanged_history_does_not_write_or_replace_case_ids(): void
    {
        $this->qualify();
        $case = $this->winter->cases()->sole();
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            app(SeasonalCaseService::class)->recalculatePlayerCampaign($this->player->id, $this->winter->id);
            $writes = collect(DB::getQueryLog())->filter(fn (array $query): bool => preg_match('/^(insert|update|delete)\b/i', $query['query']) === 1);
        } finally {
            DB::disableQueryLog();
        }

        $this->assertCount(0, $writes);
        $this->assertSame($case->getAttributes(), $case->fresh()->getAttributes());
    }

    public function test_campaign_settings_control_deadline_and_minimum_payment(): void
    {
        $this->winter->update(['required_visits' => 2, 'deadline_days' => 5, 'minimum_qualifying_payment' => 40]);
        $below = $this->visit('2027-02-20', 39);
        $this->assertNull($below->fresh()->autumn_case_id);
        $this->visit('2027-02-26', 40);
        $this->visit('2027-02-28', 40);
        $case = $this->winter->cases()->sole();
        $this->assertSame('2027-02-28', $case->deadline_at->toDateString());
        $this->assertNotNull($case->qualified_at);

        $outside = $this->visit('2027-03-01', 0);
        $this->assertNull($outside->fresh()->autumn_case_id);
    }

    public function test_dashboard_and_player_history_switch_seasons_and_keep_campaign_in_links(): void
    {
        $autumn = $this->visit('2026-11-20')->fresh()->autumnCase;
        $winter = $this->visit('2026-12-01')->fresh()->autumnCase;

        Livewire::test(AutumnCampaignDashboard::class)
            ->assertSet('campaignId', $this->winter->id)
            ->assertCanSeeTableRecords([$winter])->assertCanNotSeeTableRecords([$autumn])
            ->assertSee('1 из 6')
            ->set('campaignId', $autumn->autumn_campaign_id)
            ->assertCanSeeTableRecords([$autumn])->assertCanNotSeeTableRecords([$winter])
            ->assertSee('1 из 5')
            ->assertSee('campaign='.$autumn->autumn_campaign_id);

        Livewire::withQueryParams(['campaign' => $autumn->autumn_campaign_id])
            ->test(PlayerAutumnCase::class, ['record' => $this->player->id])
            ->assertSet('campaignId', $autumn->autumn_campaign_id)
            ->assertSee('1 из 5')->assertDontSee('1 из 6')
            ->set('campaignId', $this->winter->id)
            ->assertSee('1 из 6')->assertDontSee('1 из 5');
    }

    /** @return array<int, EveningParticipant> */
    private function qualify(): array
    {
        return array_map(fn (int $day): EveningParticipant => $this->visit(sprintf('2026-12-%02d', $day)), range(1, 6));
    }

    private function visit(string $date, int $amount = 30, ?Player $player = null): EveningParticipant
    {
        return EveningParticipant::create([
            'evening_id' => Evening::create(['played_at' => $date.' 19:00:00'])->id,
            'player_id' => ($player ?? $this->player)->id,
            'payment_type_id' => $this->cash->id,
            'paid_amount' => $amount,
        ]);
    }
}
