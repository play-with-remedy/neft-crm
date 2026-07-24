<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_categories', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
            $table->boolean('is_system')->default(false);
        });

        $now = now();

        $payrollId = DB::table('financial_categories')
            ->whereNull('parent_id')
            ->where('name', 'ФОТ')
            ->value('id');

        if ($payrollId === null) {
            $payrollId = DB::table('financial_categories')->insertGetId([
                'parent_id' => null,
                'name' => 'ФОТ',
                'code' => 'payroll',
                'is_system' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('financial_categories')
                ->where('id', $payrollId)
                ->update([
                    'code' => 'payroll',
                    'is_system' => true,
                    'updated_at' => $now,
                ]);
        }

        $financeSortOrder = (int) (
            DB::table('financial_categories')
                ->where('parent_id', $payrollId)
                ->where('name', 'Финансы')
                ->value('sort_order') ?? 0
        );

        $teamSalariesId = DB::table('financial_categories')
            ->where('code', 'team_salaries')
            ->value('id');

        if ($teamSalariesId === null) {
            $teamSalariesId = DB::table('financial_categories')->insertGetId([
                'parent_id' => $payrollId,
                'name' => 'Зарплаты команды',
                'code' => 'team_salaries',
                'is_system' => true,
                'sort_order' => $financeSortOrder + 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $children = [
            [
                'name' => 'Менеджеры, ведущие, судьи и супервайзеры',
                'code' => 'team_event_salaries',
                'sort_order' => 10,
            ],
            [
                'name' => 'Прочие расходы в игровых вечерах и турнирах',
                'code' => 'team_event_other_expenses',
                'sort_order' => 20,
            ],
        ];

        foreach ($children as $child) {
            DB::table('financial_categories')->updateOrInsert(
                ['code' => $child['code']],
                [
                    'parent_id' => $teamSalariesId,
                    'name' => $child['name'],
                    'is_system' => true,
                    'sort_order' => $child['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('financial_categories')
            ->whereIn('code', [
                'team_event_salaries',
                'team_event_other_expenses',
                'team_salaries',
            ])
            ->delete();

        Schema::table('financial_categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'is_system']);
        });
    }
};
