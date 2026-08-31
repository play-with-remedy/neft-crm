<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('autumn_campaigns')->insertOrIgnore([
            [
                'name' => 'Осеннее дело 2026',
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-11-30',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('autumn_campaigns')
            ->where('starts_at', '2026-09-01')
            ->where('ends_at', '2026-11-30')
            ->update([
                'name' => 'Осеннее дело 2026',
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Кампания может уже содержать реальные дела игроков. Не удаляем
        // пользовательские данные при откате служебной data-миграции.
    }
};
