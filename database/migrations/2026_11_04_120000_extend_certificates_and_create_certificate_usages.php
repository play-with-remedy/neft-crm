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
            $table->foreignId('seller_host_id')->nullable()->constrained('hosts')->restrictOnDelete();
            $table->date('expires_at')->nullable()->index();
            $table->unsignedSmallInteger('usage_limit')->default(1);
        });

        Schema::create('certificate_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->date('used_at');
            $table->timestamps();
            $table->unique(['certificate_id', 'used_at']);
        });

        DB::table('certificates')->where('type', 'two_for_one')->update(['usage_limit' => 2]);

        DB::table('certificates')->whereNotNull('used_at')->orderBy('id')
            ->eachById(function (object $certificate): void {
                DB::table('certificate_usages')->insert([
                    'certificate_id' => $certificate->id,
                    'used_at' => $certificate->used_at,
                    'created_at' => $certificate->created_at,
                    'updated_at' => $certificate->updated_at,
                ]);
            });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropIndex(['used_at']);
            $table->dropColumn('used_at');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->date('used_at')->nullable()->index();
        });

        DB::table('certificate_usages')
            ->selectRaw('certificate_id, MIN(used_at) as first_used_at')
            ->groupBy('certificate_id')
            ->get()
            ->each(function (object $usage): void {
                DB::table('certificates')->where('id', $usage->certificate_id)
                    ->update(['used_at' => $usage->first_used_at]);
            });

        Schema::dropIfExists('certificate_usages');

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('seller_host_id');
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['expires_at', 'usage_limit']);
        });
    }
};
