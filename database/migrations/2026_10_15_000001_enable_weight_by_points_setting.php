<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ketepatan waktu & kualitas kini dibobot poin difficulty secara default.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'weight_by_points')
            ->update(['value' => json_encode(true), 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'weight_by_points')
            ->update(['value' => json_encode(false), 'updated_at' => now()]);
    }
};
