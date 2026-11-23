<?php

namespace Tests\Feature;

use App\Models\Evening;
use App\Models\EveningType;
use App\Models\Location;
use App\Models\Player;
use App\Models\Project;
use App\Services\TelegramSchedule;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private EveningType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 12:00:00', 'UTC'));
        config()->set('services.telegram', ['bot_token' => 'test-token', 'webhook_secret' => 'test-secret']);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        $this->seed(LocationSeeder::class);
        $this->project = Project::create(['name' => 'Клуб Neft']);
        $this->type = EveningType::create(['name' => 'Спортивная мафия']);
    }

    public function test_webhook_rejects_missing_wrong_and_unconfigured_secrets(): void
    {
        $this->postJson('/telegram/webhook', $this->update('/schedule'))->assertForbidden();
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'wrong')
            ->postJson('/telegram/webhook', $this->update('/schedule'))->assertForbidden();
        config()->set('services.telegram.webhook_secret', null);
        $this->send('/schedule')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_schedule_shows_all_public_details_and_multiple_locations_to_any_user(): void
    {
        $evening = $this->evening();
        $evening->syncVenueSelections(Location::with('halls')->orderBy('id')->get()->map(fn ($location): array => [
            'location_id' => $location->id, 'hall_ids' => $location->halls->take(2)->modelKeys(),
        ])->all());
        Player::create(['nickname' => 'Секретный игрок', 'phone' => '+375291234567']);

        $this->send('/schedule')->assertOk();
        $text = $this->sentText();
        foreach (['23.11.2026, 19:30', 'Клуб Neft', 'Спортивная мафия', 'Neft — Manhattan; Neft',
            'Аранская 8', 'Кибитка — Сицилия, #21; Детройит, #30', 'Октябрьская 16/28', '30,50 руб.'] as $expected) {
            $this->assertStringContainsString($expected, $text);
        }
        $this->assertStringNotContainsString('Секретный игрок', $text);
        $this->assertStringNotContainsString('+375291234567', $text);
        $this->assertDatabaseCount('players', 1);
        Http::assertSent(fn (ClientRequest $request): bool => $request['chat_id'] === 12345
            && $request['reply_markup'] === ['keyboard' => [[TelegramSchedule::BUTTON]], 'resize_keyboard' => true]);
    }

    public function test_start_button_and_russian_text_request_only_the_schedule(): void
    {
        foreach (['/start', TelegramSchedule::BUTTON, '  РАСПИСАНИЕ  ', '/schedule@neft_mafia_club_bot'] as $index => $text) {
            $this->send($text, $index + 1)->assertOk();
        }
        Http::assertSentCount(4);
        Http::assertNotSent(fn (ClientRequest $request): bool => ! str_contains($request['text'], 'На ближайшие 7 дней игр пока нет'));
    }

    public function test_window_uses_minsk_calendar_days_and_excludes_started_and_later_evenings(): void
    {
        $this->travelTo(Carbon::parse('2026-11-22 21:30:00', 'UTC')); // 23 November, 00:30 in Minsk.
        foreach (['2026-11-22 23:59:00', '2026-11-23 00:29:00', '2026-11-30 00:00:00'] as $date) {
            $this->evening($date);
        }
        $this->evening('2026-11-29 23:59:00');
        $this->evening('2026-11-23 00:31:00');

        $this->send('/schedule')->assertOk();
        $text = $this->sentText();
        $this->assertStringContainsString('23.11.2026–29.11.2026', $text);
        $this->assertStringContainsString('23.11.2026, 00:31', $text);
        $this->assertStringContainsString('29.11.2026, 23:59', $text);
        foreach (['22.11.2026, 23:59', '23.11.2026, 00:29', '30.11.2026, 00:00'] as $excluded) {
            $this->assertStringNotContainsString($excluded, $text);
        }
        $this->assertLessThan(strpos($text, '29.11.2026, 23:59'), strpos($text, '23.11.2026, 00:31'));
    }

    public function test_every_new_request_reads_updated_schedule(): void
    {
        $evening = $this->evening();
        $this->send('/schedule', 1)->assertOk();
        $evening->update(['played_at' => '2026-11-24 21:15:00', 'price' => 0]);
        $this->send('/schedule', 2)->assertOk();
        $second = Http::recorded()->values()[1][0]['text'];
        $this->assertStringContainsString('24.11.2026, 21:15', $second);
        $this->assertStringContainsString('Бесплатно', $second);
        $this->assertStringNotContainsString('23.11.2026, 19:30', $second);
        $evening->delete();
        $this->send('/schedule', 3)->assertOk();
        $this->assertStringContainsString('игр пока нет', Http::recorded()->values()[2][0]['text']);
    }

    public function test_unfilled_schedule_fields_are_described_without_exposing_internal_data(): void
    {
        Evening::create(['played_at' => '2026-11-24 20:00:00']);
        $this->send('/schedule')->assertOk();
        $this->assertStringContainsString('Локация уточняется', $this->sentText());
        $this->assertStringContainsString('Стоимость уточняется', $this->sentText());
    }

    public function test_old_commands_buttons_drafts_and_cached_player_replies_cannot_create_or_lookup_players(): void
    {
        Cache::put('telegram:player-draft:12345', ['stage' => 'confirm', 'nickname' => 'Новичок'], 1200);
        Cache::put('telegram:reply:1', ['text' => 'Старое подтверждение игрока', 'reply_markup' => []], 1200);
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            foreach (['/add', '➕ Добавить игрока', '✅ Сохранить', '🔎 Проверить ник', 'Новичок'] as $index => $text) {
                $update = $this->update($text, $index + 1);
                $update['message']['from']['username'] = 'DyrachyoGaming';
                $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
                    ->postJson('/telegram/webhook', $update)->assertOk();
            }
            $queries = collect(DB::getQueryLog())->pluck('query')->join("\n");
        } finally {
            DB::disableQueryLog();
        }
        $this->assertStringNotContainsString('players', $queries);
        $this->assertDatabaseCount('players', 0);
        Http::assertNotSent(fn (ClientRequest $request): bool => ! str_contains($request['text'], 'ближайшие игры клуба')
            || $request['reply_markup']['keyboard'] !== [[TelegramSchedule::BUTTON]]);
    }

    public function test_multiple_games_load_public_relations_in_a_fixed_number_of_queries(): void
    {
        $location = Location::where('name', 'Neft')->with('halls')->sole();
        for ($index = 0; $index < 20; $index++) {
            $this->evening()->syncVenueSelections([['location_id' => $location->id, 'hall_ids' => $location->halls->modelKeys()]]);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->send('/schedule')->assertOk();
            $queries = collect(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
        $this->assertCount(6, $queries);
    }

    public function test_repeated_updates_do_not_send_duplicate_replies(): void
    {
        $this->send('/schedule')->assertOk();
        $this->send('/schedule')->assertOk();
        Http::assertSentCount(1);
    }

    public function test_group_non_text_bot_and_invalid_updates_are_ignored(): void
    {
        $update = $this->update('/schedule');
        $update['message']['chat']['type'] = 'group';
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')->postJson('/telegram/webhook', $update)->assertOk();
        $update['message']['chat']['type'] = 'private';
        $update['message']['from']['is_bot'] = true;
        $this->postJson('/telegram/webhook', $update)->assertOk();
        $update['message']['from']['is_bot'] = false;
        unset($update['message']['text']);
        $this->postJson('/telegram/webhook', $update)->assertOk();
        $this->postJson('/telegram/webhook', $this->update('/schedule', -1))->assertOk();
        Http::assertNothingSent();
    }

    public function test_long_schedule_is_split_and_retry_resumes_after_successful_parts(): void
    {
        $this->project->update(['name' => str_repeat('Я', 250)]);
        for ($index = 0; $index < 30; $index++) {
            $this->evening('2026-11-24 18:'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).':00');
        }
        $expected = app(TelegramSchedule::class)->reply($this->update('/schedule')['message'])['messages'];
        $this->assertGreaterThan(1, count($expected));
        $sequence = Http::sequence()->push(['ok' => true, 'result' => ['message_id' => 1]])->push(['ok' => false], 500);
        foreach (array_slice($expected, 1) as $unused) {
            $sequence->push(['ok' => true, 'result' => ['message_id' => 2]]);
        }
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => $sequence]);
        $this->send('/schedule')->assertStatus(503)->assertExactJson(['ok' => false]);
        $this->send('/schedule')->assertOk();
        $this->send('/schedule')->assertOk();
        Http::assertSentCount(count($expected) + 1);
        $this->assertCount(1, Http::recorded(fn (ClientRequest $request): bool => $request['text'] === $expected[0]));
        foreach ($expected as $message) {
            $this->assertLessThanOrEqual(4000, strlen(mb_convert_encoding($message, 'UTF-16LE', 'UTF-8')) / 2);
        }
        foreach (range(0, 29) as $index) {
            $this->assertStringContainsString('18:'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), implode("\n", $expected));
        }
    }

    public function test_single_oversized_game_keeps_unicode_valid_and_fits_telegram_messages(): void
    {
        foreach (Location::with('halls')->get() as $location) {
            $location->update(['address' => str_repeat('🎲', 400)]);
            foreach ($location->halls as $index => $hall) {
                $hall->update(['name' => str_repeat('🎲', 240).$index]);
            }
        }
        $evening = $this->evening();
        $evening->syncVenueSelections(Location::with('halls')->get()->map(fn ($location): array => [
            'location_id' => $location->id, 'hall_ids' => $location->halls->modelKeys(),
        ])->all());
        $this->send('/schedule')->assertOk();
        $messages = Http::recorded()->map(fn ($pair): string => $pair[0]['text']);
        $this->assertGreaterThan(1, $messages->count());
        foreach ($messages as $message) {
            $this->assertTrue(mb_check_encoding($message, 'UTF-8'));
            $this->assertLessThanOrEqual(4000, strlen(mb_convert_encoding($message, 'UTF-16LE', 'UTF-8')) / 2);
        }
        $this->assertStringContainsString('30,50 руб.', $messages->join("\n"));
    }

    public function test_failed_request_can_retry_without_exposing_the_token(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::sequence()->push(['ok' => false], 500)->push(['ok' => true, 'result' => true])]);
        $this->send('/schedule')->assertStatus(503)->assertExactJson(['ok' => false]);
        $this->send('/schedule')->assertOk();
        Http::assertSentCount(2);
    }

    public function test_one_chat_cannot_request_more_than_twenty_replies_per_minute(): void
    {
        for ($id = 1; $id <= 21; $id++) {
            $this->send('/schedule', $id)->assertOk();
        }
        Http::assertSentCount(20);
    }

    public function test_busy_locks_return_a_retryable_response(): void
    {
        foreach (['telegram:update-lock:1', 'telegram:chat-lock:12345'] as $key) {
            $lock = Cache::lock($key, 30);
            $this->assertTrue($lock->get());
            try {
                $this->send('/schedule')->assertStatus(503);
            } finally {
                $lock->release();
            }
        }
        Http::assertNothingSent();
        $this->send('/schedule')->assertOk();
    }

    public function test_setup_registers_only_schedule_and_updates_bot_description(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            'api.telegram.org/bottest-token/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'test_bot']]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);
        $this->artisan('telegram:setup', ['--url' => 'https://example.com/telegram/webhook'])
            ->expectsOutput('Подключён бот @test_bot')->assertSuccessful();
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/setWebhook')
            && $request['secret_token'] === 'test-secret' && $request['allowed_updates'] === ['message']);
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/setMyCommands')
            && $request['commands'] === [['command' => 'schedule', 'description' => 'Расписание игр на ближайшие 7 дней']]);
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/setMyDescription')
            && str_contains($request['description'], 'Ближайшие игры клуба'));
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/setMyShortDescription')
            && str_contains($request['short_description'], 'Расписание игр клуба'));
    }

    private function evening(string $date = '2026-11-23 19:30:00'): Evening
    {
        return Evening::create(['played_at' => $date, 'project_id' => $this->project->id,
            'evening_type_id' => $this->type->id, 'price' => 30.50]);
    }

    private function send(string $text, int $id = 1): TestResponse
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $this->update($text, $id));
    }

    private function sentText(): string
    {
        return Http::recorded()->map(fn ($pair): string => $pair[0]['text'])->join("\n");
    }

    private function update(string $text, int $id = 1): array
    {
        return ['update_id' => $id, 'message' => [
            'from' => ['id' => 12345, 'is_bot' => false],
            'chat' => ['id' => 12345, 'type' => 'private'], 'text' => $text,
        ]];
    }
}
