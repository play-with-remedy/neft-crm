<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->foreignId('autumn_case_id')
                ->nullable()
                ->after('player_id')
                ->constrained('autumn_cases')
                ->nullOnDelete();
            $table->unsignedTinyInteger('autumn_case_visit_number')
                ->nullable()
                ->after('autumn_case_id');
            $table->boolean('is_autumn_reward')
                ->default(false)
                ->after('autumn_case_visit_number');

            $table->unique(
                ['autumn_case_id', 'autumn_case_visit_number'],
                'autumn_case_visit_unique',
            );
            $table->index(
                ['autumn_case_id', 'is_autumn_reward'],
                'autumn_case_reward_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->dropUnique('autumn_case_visit_unique');
            $table->dropIndex('autumn_case_reward_index');
            $table->dropForeign(['autumn_case_id']);
            $table->dropColumn([
                'autumn_case_id',
                'autumn_case_visit_number',
                'is_autumn_reward',
            ]);
        });
    }
};
