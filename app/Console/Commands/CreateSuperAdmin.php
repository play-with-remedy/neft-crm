<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-superadmin';

    protected $description = 'Безопасно создаёт первого суперадмина';

    public function handle(): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            $this->error('Сначала выполните миграции: php artisan migrate --force');

            return self::FAILURE;
        }

        if (User::query()->where('role', UserRole::SuperAdmin->value)->exists()) {
            $this->error('Суперадмин уже существует. Новые учётные записи создавайте в админпанели.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Имя'));
        $email = mb_strtolower(trim((string) $this->ask('Email (логин для входа)')));
        $password = (string) $this->secret('Пароль (минимум 8 символов)');
        $passwordConfirmation = (string) $this->secret('Повторите пароль');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class, 'email'),
                ],
                'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            ],
            [],
            [
                'name' => 'имя',
                'email' => 'email',
                'password' => 'пароль',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::INVALID;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::SuperAdmin,
        ]);

        $this->info("Суперадмин {$user->email} создан. Вход: /admin");

        return self::SUCCESS;
    }
}
