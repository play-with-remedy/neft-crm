<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Services\TelegramPlayerConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
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
            'player_creator_username' => 'DyrachyoGaming',
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
            && $request['text'] === 'Такой ник уже есть в клубе 🙂');
    }

    public function test_lookup_requires_an_exact_nickname_and_does_not_treat_sql_wildcards_as_patterns(): void
    {
        Player::create(['nickname' => 'Игрок']);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $this->update('Иг%'))->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool => $request['text'] === 'Такого ника в клубе пока нет — он свободен! ✨');
    }

    public function test_start_explains_how_to_check_a_nickname(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $this->update('/start'))->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request['text'], 'Пришли игровой ник')
            && str_contains($request['text'], 'только на русском')
            && $request['reply_markup'] === ['remove_keyboard' => true]);
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
        Http::swap(new Factory);
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
        Http::swap(new Factory);
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

    public function test_only_russian_nicknames_are_accepted(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret');

        foreach (['Player', 'Мaфия', '123', '🙂', str_repeat('Я', 256)] as $index => $nickname) {
            $this->postJson('/telegram/webhook', $this->update($nickname, $index + 1))->assertOk();
        }

        Http::assertSentCount(5);
        Http::assertNotSent(fn (ClientRequest $request): bool => ! str_contains($request['text'], 'только на русском'));
        $this->assertDatabaseCount('players', 0);
    }

    public function test_public_users_cannot_create_players_even_when_sending_hidden_commands(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret');
        $this->postJson('/telegram/webhook', $this->update('/add', 1, 'AnotherUser'))->assertOk();
        $this->postJson('/telegram/webhook', $this->update('Новичок', 2, 'AnotherUser'))->assertOk();
        $this->postJson('/telegram/webhook', $this->update(TelegramPlayerConversation::SAVE, 3, 'AnotherUser'))->assertOk();

        $this->assertDatabaseCount('players', 0);
        Http::assertNotSent(fn (ClientRequest $request): bool => isset($request['reply_markup']['keyboard']));
    }

    public function test_creator_has_an_add_player_button(): void
    {
        $this->sendAsCreator('/start', 1);

        Http::assertSent(fn (ClientRequest $request): bool => in_array(TelegramPlayerConversation::ADD,
            $request['reply_markup']['keyboard'][0], true));
    }

    public function test_creator_can_save_a_nickname_after_confirmation_without_setting_first_visit(): void
    {
        $this->sendAsCreator(TelegramPlayerConversation::ADD, 1);
        $this->sendAsCreator('  Новый игрок  ', 2);
        $this->assertDatabaseCount('players', 0);
        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 3);

        $player = Player::query()->sole();
        $this->assertSame('Новый игрок', $player->nickname);
        $this->assertNull($player->first_visit_at);
        $this->assertNull($player->first_name);
        $this->assertNull($player->telegram);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request['text'], 'теперь в клубе 🎉'));
    }

    public function test_existing_nicknames_cannot_be_added_with_different_case(): void
    {
        Player::create(['nickname' => 'Новичок']);
        $this->sendAsCreator('/add', 1);
        $this->sendAsCreator('НОВИЧОК', 2);
        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 3);

        $this->assertDatabaseCount('players', 1);
        Http::assertNotSent(fn (ClientRequest $request): bool => str_contains($request['text'], 'теперь в клубе'));
    }

    public function test_nickname_is_checked_again_if_someone_creates_it_before_confirmation(): void
    {
        $this->sendAsCreator('/add', 1);
        $this->sendAsCreator('Новичок', 2);
        Player::create(['nickname' => 'НОВИЧОК']);
        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 3);

        $this->assertDatabaseCount('players', 1);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request['text'], 'Ещё одну карточку создавать не будем'));
    }

    public function test_failed_confirmation_reply_is_retried_without_creating_a_second_player(): void
    {
        $this->sendAsCreator('/add', 1);
        $this->sendAsCreator('Новичок', 2);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::sequence()
            ->push(['ok' => false], 500)
            ->push(['ok' => true, 'result' => ['message_id' => 1]])]);

        $this->postJson('/telegram/webhook', $this->update(TelegramPlayerConversation::SAVE, 3, 'DyrachyoGaming'))
            ->assertStatus(503);
        $this->assertDatabaseCount('players', 1);
        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 3);
        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 3);

        $this->assertDatabaseCount('players', 1);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (ClientRequest $request): bool => ! str_contains($request['text'], 'теперь в клубе 🎉'));
    }

    public function test_cancelling_or_expiring_a_draft_does_not_create_a_player(): void
    {
        $this->sendAsCreator('/add', 1);
        $this->sendAsCreator('Новичок', 2);
        $this->sendAsCreator(TelegramPlayerConversation::CANCEL, 3);
        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 4);
        $this->sendAsCreator('/add', 5);
        $this->sendAsCreator('Новичок', 6);
        $this->travel(21)->minutes();
        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 7);

        $this->assertDatabaseCount('players', 0);
    }

    public function test_access_is_rechecked_before_saving_a_draft(): void
    {
        $this->sendAsCreator('/add', 1);
        $this->sendAsCreator('Новичок', 2);
        $this->postJson('/telegram/webhook', $this->update(TelegramPlayerConversation::SAVE, 3, 'AnotherUser'))->assertOk();

        $this->assertDatabaseCount('players', 0);
    }

    public function test_forwarded_sender_or_chat_username_cannot_grant_creation_access(): void
    {
        $update = $this->update('/add', 1, 'AnotherUser');
        $update['message']['chat']['username'] = 'DyrachyoGaming';
        $update['message']['forward_from'] = ['id' => 99, 'username' => 'DyrachyoGaming'];
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')->postJson('/telegram/webhook', $update)->assertOk();

        Http::assertNotSent(fn (ClientRequest $request): bool => isset($request['reply_markup']['keyboard']));
    }

    public function test_busy_update_and_chat_locks_return_a_retryable_response(): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret');

        foreach (['telegram:update-lock:1', 'telegram:chat-lock:12345'] as $key) {
            $lock = Cache::lock($key, 30);
            $this->assertTrue($lock->get());

            try {
                $this->postJson('/telegram/webhook', $this->update('Игрок'))->assertStatus(503);
            } finally {
                $lock->release();
            }
        }

        Http::assertNothingSent();
        $this->postJson('/telegram/webhook', $this->update('Игрок'))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_busy_nickname_lock_keeps_the_draft_for_a_later_save(): void
    {
        $this->sendAsCreator('/add', 1);
        $this->sendAsCreator('Новичок', 2);
        $lock = Cache::lock('telegram:player-create:'.hash('sha256', mb_strtolower('Новичок')), 30);
        $this->assertTrue($lock->get());

        try {
            $this->sendAsCreator(TelegramPlayerConversation::SAVE, 3);
            $this->assertDatabaseCount('players', 0);
        } finally {
            $lock->release();
        }

        $this->sendAsCreator(TelegramPlayerConversation::SAVE, 4);
        $this->assertDatabaseCount('players', 1);
    }

    private function sendAsCreator(string $text, int $id): void
    {
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-secret')
            ->postJson('/telegram/webhook', $this->update($text, $id, 'dyrachyogaming'))->assertOk();
    }

    private function update(string $text, int $id = 1, ?string $username = null): array
    {
        return [
            'update_id' => $id,
            'message' => [
                'from' => ['id' => 12345, 'is_bot' => false, 'username' => $username],
                'chat' => ['id' => 12345, 'type' => 'private'],
                'text' => $text,
            ],
        ];
    }
}
