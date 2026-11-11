<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nama tabel ditulis literal (bukan loop) agar bisa dibaca Larastan untuk inferensi properti model.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_requests', function (Blueprint $table): void {
            $table->foreignId('escalated_by')->nullable()->constrained('users');
            $table->timestampTz('escalated_at')->nullable();
            $table->text('escalation_note')->nullable();
        });

        Schema::table('bug_reports', function (Blueprint $table): void {
            $table->foreignId('escalated_by')->nullable()->constrained('users');
            $table->timestampTz('escalated_at')->nullable();
            $table->text('escalation_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bug_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('escalated_by');
            $table->dropColumn(['escalated_at', 'escalation_note']);
        });

        Schema::table('feature_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('escalated_by');
            $table->dropColumn(['escalated_at', 'escalation_note']);
        });
    }
};
