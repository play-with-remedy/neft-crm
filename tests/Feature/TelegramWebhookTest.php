<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram', [
            'bot_token' => 'test-token',
            'webhook_secret' => 'test-secret',
        ]);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);

        // Match the Unicode LOWER support of production MySQL/PostgreSQL.
        DB::connection()->getPdo()->sqliteCreateFunction('lower', fn ($value) => mb_strtolower($value), 1);
    }

    public function test_webhook_rejects_missing_or_wrong_secret(): void
    {
        $this->postJson('/telegram/webhook', $this->update('Player'))->assertForbidden();
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'wrong')
            ->postJson('/telegram/webhook', $this->update('Player'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_webhook_is_disabled_without_configured_secret(): void
    {
        config()->set('services.telegram.webhook_secret', null);
        $this->postJson('/telegram/webhook', $this->update('Player'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_nickname_lookup_ignores_case_and_surrounding_spaces_without_exposing_player_details(): void
    {
        Player::create(['nickname' => ' Мафия ', 'first_name' => 'Секретное имя', 'phone' => '+375291234567']);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $this->update('  мАфИя  '))->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool => $request['chat_id'] === 12345
            && $request['text'] === 'Такой ник уже есть в базе.');
    }

    public function test_lookup_requires_an_exact_nickname_and_does_not_treat_sql_wildcards_as_patterns(): void
    {
        Player::create(['nickname' => 'Player']);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $this->update('Play%'))->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool => $request['text'] === 'Такого ника в базе нет.');
    }

    public function test_start_explains_how_to_check_a_nickname(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $this->update('/start'))->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request['text'], 'Пришлите игровой ник'));
    }

    public function test_group_and_non_text_updates_are_ignored(): void
    {
        $update = $this->update('Player');
        $update['message']['chat']['type'] = 'group';
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $update)->assertOk();

        unset($update['message']['text']);
        $update['message']['chat']['type'] = 'private';
        $this->postJson('/telegram/webhook', $update)->assertOk();
        Http::assertNothingSent();
    }

    public function test_repeated_updates_do_not_send_duplicate_replies(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret');
        $this->postJson('/telegram/webhook', $this->update('Player'))->assertOk();
        $this->postJson('/telegram/webhook', $this->update('Player'))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_failed_telegram_request_can_be_retried_without_exposing_the_token(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::sequence()
            ->push(['ok' => false], 500)
            ->push(['ok' => true, 'result' => ['message_id' => 1]])]);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret');
        $this->postJson('/telegram/webhook', $this->update('Player'))
            ->assertStatus(503)->assertExactJson(['ok' => false]);
        $this->postJson('/telegram/webhook', $this->update('Player'))->assertOk();
        Http::assertSentCount(2);
    }

    public function test_one_chat_cannot_send_more_than_twenty_replies_per_minute(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret');

        for ($id = 1; $id <= 21; $id++) {
            $this->postJson('/telegram/webhook', $this->update('Player', $id))->assertOk();
        }

        Http::assertSentCount(20);
    }

    public function test_setup_registers_the_secret_and_only_message_updates(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake([
            'api.telegram.org/bottest-token/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'test_bot']]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => true]),
        ]);

        $this->artisan('telegram:setup', ['--url' => 'https://example.com/telegram/webhook'])
            ->expectsOutput('Подключён бот @test_bot')->assertSuccessful();

        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/setWebhook')
            && $request['secret_token'] === 'test-secret'
            && $request['allowed_updates'] === ['message']
            && $request['url'] === 'https://example.com/telegram/webhook');
    }

    private function update(string $text, int $id = 1): array
    {
        return [
            'update_id' => $id,
            'message' => [
                'from' => ['id' => 12345, 'is_bot' => false],
                'chat' => ['id' => 12345, 'type' => 'private'],
                'text' => $text,
            ],
        ];
    }
}
