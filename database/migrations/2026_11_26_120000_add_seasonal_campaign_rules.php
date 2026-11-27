<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the existing storage and IDs so the autumn history remains intact.
        Schema::table('autumn_campaigns', function (Blueprint $table): void {
            $table->unsignedTinyInteger('required_visits')->default(5);
            $table->unsignedSmallInteger('deadline_days')->default(30);
            $table->decimal('minimum_qualifying_payment', 10, 2)->default(30);
            $table->boolean('reward_requires_zero_payment')->default(false);
        });

        DB::table('autumn_campaigns')->upsert([[
            'name' => 'Зимнее дело 2026–2027',
            'starts_at' => '2026-12-01',
            'ends_at' => '2027-02-28',
            'is_active' => true,
            'required_visits' => 6,
            'deadline_days' => 30,
            'minimum_qualifying_payment' => 30,
            'reward_requires_zero_payment' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['starts_at', 'ends_at'], [
            'required_visits', 'deadline_days', 'minimum_qualifying_payment', 'reward_requires_zero_payment',
        ]);
    }

    public function down(): void
    {
        // Campaigns may contain real visits; do not remove their history on rollback.
        Schema::table('autumn_campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'required_visits', 'deadline_days', 'minimum_qualifying_payment',
                'reward_requires_zero_payment',
            ]);
        });
    }
};
