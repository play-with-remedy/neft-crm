<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $payrollId = DB::table('financial_categories')
            ->where('code', 'payroll')
            ->value('id');

        if ($payrollId === null) {
            return;
        }

        $finance = DB::table('financial_categories')
            ->where('parent_id', $payrollId)
            ->where('name', 'Финансы')
            ->first();

        $teamSalariesId = DB::table('financial_categories')
            ->where('code', 'team_salaries')
            ->value('id');

        if ($finance === null || $teamSalariesId === null) {
            return;
        }

        DB::table('financial_categories')
            ->where('id', $finance->id)
            ->update([
                'code' => 'payroll_finance',
                'is_system' => true,
                'updated_at' => now(),
            ]);

        DB::table('financial_categories')
            ->where('parent_id', $payrollId)
            ->where('id', '!=', $teamSalariesId)
            ->where('sort_order', '>', $finance->sort_order)
            ->increment('sort_order', 10);

        DB::table('financial_categories')
            ->where('id', $teamSalariesId)
            ->update([
                'sort_order' => $finance->sort_order + 10,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('financial_categories')
            ->where('code', 'payroll_finance')
            ->update([
                'code' => null,
                'is_system' => false,
                'updated_at' => now(),
            ]);
    }
};
