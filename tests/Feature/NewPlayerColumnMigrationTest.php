<?php

namespace Tests\Feature;

use App\Models\Evening;
use App\Models\PaymentType;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NewPlayerColumnMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_removing_legacy_flag_preserves_participations_and_payments(): void
    {
        $this->assertFalse(Schema::hasColumn('evening_participants', 'is_new_player'));
        $migration = require database_path('migrations/2026_11_20_120000_drop_is_new_player_from_evening_participants_table.php');
        $migration->down();

        $evening = Evening::create(['played_at' => '2026-11-20']);
        $player = Player::create(['nickname' => 'Migration player']);
        $paymentType = PaymentType::create(['type' => 'Cash']);
        $participant = $evening->participants()->create([
            'player_id' => $player->id,
            'payment_type_id' => $paymentType->id,
            'paid_amount' => 32.50,
            'note' => 'Keep this note',
        ]);
        DB::table('evening_participants')->where('id', $participant->id)->update(['is_new_player' => true]);

        $before = (array) DB::table('evening_participants')->where('id', $participant->id)->first();
        unset($before['is_new_player']);
        $payments = DB::table('evening_participant_payments')->get()->toArray();

        $migration->up();

        $this->assertFalse(Schema::hasColumn('evening_participants', 'is_new_player'));
        $this->assertEquals($before, (array) DB::table('evening_participants')->where('id', $participant->id)->first());
        $this->assertEquals($payments, DB::table('evening_participant_payments')->get()->toArray());
    }
}
