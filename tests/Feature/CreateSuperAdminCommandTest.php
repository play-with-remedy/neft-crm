<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_superadmin_with_a_hidden_password_prompt(): void
    {
        $this->artisan('app:create-superadmin')
            ->expectsQuestion('Имя', 'Владелец')
            ->expectsQuestion('Email (логин для входа)', 'OWNER@EXAMPLE.COM')
            ->expectsQuestion('Пароль (минимум 8 символов)', 'secure-password')
            ->expectsQuestion('Повторите пароль', 'secure-password')
            ->expectsOutput('Суперадмин owner@example.com создан. Вход: /admin')
            ->assertSuccessful();

        $user = User::query()->sole();

        $this->assertSame('Владелец', $user->name);
        $this->assertSame('owner@example.com', $user->email);
        $this->assertSame(UserRole::SuperAdmin, $user->role);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertNotSame('secure-password', $user->password);
    }

    public function test_it_does_not_create_or_overwrite_another_superadmin(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'role' => UserRole::SuperAdmin,
        ]);

        $this->artisan('app:create-superadmin')
            ->expectsOutput('Суперадмин уже существует. Новые учётные записи создавайте в админпанели.')
            ->assertFailed();

        $this->assertSame(1, User::query()->count());
        $this->assertSame($existing->id, User::query()->sole()->id);
    }

    public function test_it_rejects_invalid_credentials_without_creating_a_user(): void
    {
        $this->artisan('app:create-superadmin')
            ->expectsQuestion('Имя', '')
            ->expectsQuestion('Email (логин для входа)', 'not-an-email')
            ->expectsQuestion('Пароль (минимум 8 символов)', 'short')
            ->expectsQuestion('Повторите пароль', 'different')
            ->assertExitCode(2);

        $this->assertDatabaseCount('users', 0);
    }
}
