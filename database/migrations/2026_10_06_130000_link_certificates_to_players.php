<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreignId('player_id')
                ->nullable()
                ->after('type')
                ->constrained()
                ->restrictOnDelete();
        });

        DB::table('certificates')
            ->whereNull('player_id')
            ->orderBy('id')
            ->eachById(function (object $certificate): void {
                $playerId = DB::table('players')
                    ->where('nickname', $certificate->purchaser_nickname)
                    ->value('id');

                if ($playerId !== null) {
                    DB::table('certificates')
                        ->where('id', $certificate->id)
                        ->update(['player_id' => $playerId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('player_id');
        });
    }
};
