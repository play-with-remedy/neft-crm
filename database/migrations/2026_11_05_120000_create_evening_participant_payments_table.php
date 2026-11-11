<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evening_participant_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evening_participant_id')
                ->constrained('evening_participants')
                ->cascadeOnDelete();
            $table->foreignId('payment_type_id')
                ->constrained('payment_types')
                ->restrictOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['evening_participant_id', 'payment_type_id'],
                'participant_payment_type_unique',
            );
        });

        DB::table('evening_participants')
            ->select(['id', 'payment_type_id', 'paid_amount', 'created_at', 'updated_at'])
            ->whereNotNull('payment_type_id')
            ->orderBy('id')
            ->chunkById(500, function ($participants): void {
                DB::table('evening_participant_payments')->insert(
                    $participants->map(fn ($participant): array => [
                        'evening_participant_id' => $participant->id,
                        'payment_type_id' => $participant->payment_type_id,
                        'amount' => $participant->paid_amount,
                        'created_at' => $participant->created_at,
                        'updated_at' => $participant->updated_at,
                    ])->all(),
                );
            });

        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->foreignId('payment_type_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evening_participant_payments');

        Schema::table('evening_participants', function (Blueprint $table): void {
            $table->foreignId('payment_type_id')->nullable(false)->change();
        });
    }
};
