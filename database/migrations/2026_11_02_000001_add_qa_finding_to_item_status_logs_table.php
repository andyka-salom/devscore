<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KPI Programmer mengikuti dokumen "KPI IT Development untuk Programmer":
 * - kategori bug hasil testing QA dicatat per keputusan (skor kualitas),
 * - poin task sama dengan difficulty,
 * - pengaturan lama (target bulanan, toleransi estimasi, bobot poin opsional) tidak dipakai lagi.
 */
return new class extends Migration
{
    private const array OBSOLETE_SETTINGS = ['monthly_target_points', 'on_time_tolerance', 'weight_by_points'];

    public function up(): void
    {
        Schema::table('item_status_logs', function (Blueprint $table): void {
            $table->string('qa_finding', 10)->nullable()->after('reason');
        });

        DB::table('settings')
            ->where('key', 'difficulty_points')
            ->update(['value' => json_encode(['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5]), 'updated_at' => now()]);

        DB::table('settings')->whereIn('key', self::OBSOLETE_SETTINGS)->delete();
    }

    public function down(): void
    {
        Schema::table('item_status_logs', function (Blueprint $table): void {
            $table->dropColumn('qa_finding');
        });

        DB::table('settings')
            ->where('key', 'difficulty_points')
            ->update(['value' => json_encode(['1' => 1, '2' => 2, '3' => 3, '4' => 5, '5' => 8]), 'updated_at' => now()]);
    }
};
