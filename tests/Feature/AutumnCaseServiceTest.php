<?php

namespace Tests\Feature;

use App\Enums\AutumnCaseStatus;
use App\Models\AutumnCampaign;
use App\Models\AutumnCase;
use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Models\PaymentType;
use App\Models\Player;
use App\Services\AutumnCaseService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutumnCaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutumnCampaign $campaign;

    private Player $player;

    private PaymentType $paymentType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaign = AutumnCampaign::query()->updateOrCreate(
            [
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-11-30',
            ],
            [
                'name' => 'Осеннее дело 2026',
                'is_active' => true,
            ],
        );

        $this->player = Player::query()->create([
            'nickname' => 'Осенний игрок',
            'first_name' => 'Игрок',
            'gender' => 'male',
            'birth_day' => 1,
            'birth_month' => 1,
        ]);

        $this->paymentType = PaymentType::query()->firstOrCreate(['type' => 'Наличные']);
    }

    public function test_first_visit_opens_case_with_deadline_plus_thirty_days(): void
    {
        $visit = $this->visit('2026-09-01');
        $case = AutumnCase::query()->sole();

        $this->assertSame(AutumnCaseStatus::InProgress, $case->status);
        $this->assertSame(1, $case->number);
        $this->assertSame('2026-10-01', $case->deadline_at->toDateString());
        $this->assertSame(1, $visit->fresh()->autumn_case_visit_number);
    }

    public function test_payment_below_thirty_does_not_open_or_advance_case(): void
    {
        $belowThreshold = $this->visit('2026-09-01', 29);

        $this->assertNull($belowThreshold->fresh()->autumn_case_id);
        $this->assertDatabaseCount('autumn_cases', 0);

        $firstQualifiedVisit = $this->visit('2026-09-02', 30);
        $secondBelowThreshold = $this->visit('2026-09-03', 29);

        $this->assertSame(1, $firstQualifiedVisit->fresh()->autumn_case_visit_number);
        $this->assertNull($secondBelowThreshold->fresh()->autumn_case_id);
        $this->assertSame(1, AutumnCase::query()->sole()->participations()->count());
    }

    public function test_fifth_visit_on_deadline_unlocks_and_next_visit_redeems_reward(): void
    {
        foreach (['2026-09-01', '2026-09-08', '2026-09-15', '2026-09-22', '2026-10-01'] as $date) {
            $this->visit($date);
        }

        $case = AutumnCase::query()->sole();
        $this->assertSame(AutumnCaseStatus::RewardAvailable, $case->status);
        $this->assertSame('2026-10-01', $case->qualified_at->toDateString());

        $reward = $this->visit('2026-10-20', 50);

        $this->assertTrue($reward->fresh()->is_autumn_reward);
        $this->assertSame(50.0, (float) $reward->fresh()->paid_amount);
        $this->assertSame(AutumnCaseStatus::Completed, $case->fresh()->status);
    }

    public function test_free_visit_can_redeem_an_earned_reward(): void
    {
        foreach (['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04', '2026-09-05'] as $date) {
            $this->visit($date, 30);
        }

        $reward = $this->visit('2026-09-06', 0);

        $this->assertTrue($reward->fresh()->is_autumn_reward);
        $this->assertSame(AutumnCaseStatus::Completed, AutumnCase::query()->sole()->status);
    }

    public function test_visit_after_deadline_expires_old_case_and_opens_next_case(): void
    {
        $this->visit('2026-09-01');
        $this->visit('2026-09-10');
        $visit = $this->visit('2026-10-02');

        $cases = AutumnCase::query()->orderBy('number')->get();

        $this->assertCount(2, $cases);
        $asOf = CarbonImmutable::parse('2026-10-02');
        $this->assertSame(AutumnCaseStatus::Expired, $cases[0]->statusAt($asOf));
        $this->assertSame(AutumnCaseStatus::InProgress, $cases[1]->statusAt($asOf));
        $this->assertSame(1, $visit->fresh()->autumn_case_visit_number);
    }

    public function test_visit_after_reward_opens_new_case(): void
    {
        foreach (['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04', '2026-09-05'] as $date) {
            $this->visit($date);
        }

        $this->visit('2026-09-06');
        $nextVisit = $this->visit('2026-09-07');

        $this->assertSame(2, AutumnCase::query()->count());
        $this->assertSame(2, $nextVisit->fresh()->autumnCase->number);
        $this->assertSame(1, $nextVisit->fresh()->autumn_case_visit_number);
    }

    public function test_late_november_case_is_capped_at_campaign_end(): void
    {
        $this->visit('2026-11-20');

        $this->assertSame(
            '2026-11-30',
            AutumnCase::query()->sole()->deadline_at->toDateString(),
        );

        $outsideVisit = $this->visit('2026-12-01');
        $this->assertNull($outsideVisit->fresh()->autumn_case_id);
    }

    public function test_unredeemed_reward_expires_after_campaign(): void
    {
        foreach (['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04', '2026-09-05'] as $date) {
            $this->visit($date);
        }

        $this->assertSame(
            AutumnCaseStatus::RewardExpired,
            AutumnCase::query()->sole()->statusAt(CarbonImmutable::parse('2026-12-01')),
        );
    }

    private function visit(string $date, int $amount = 30): EveningParticipant
    {
        $evening = Evening::query()->create(['played_at' => $date]);

        return EveningParticipant::query()->create([
            'evening_id' => $evening->getKey(),
            'player_id' => $this->player->getKey(),
            'payment_type_id' => $this->paymentType->getKey(),
            'paid_amount' => $amount,
            'is_new_player' => false,
            'is_full_payment' => true,
        ]);
    }
}
