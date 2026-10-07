<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->timestampTz('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestampsTz();

            $table->unique(['year', 'month']);
        });

        Schema::create('kpi_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kpi_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('role', 20);
            $table->jsonb('metrics');
            $table->decimal('final_score', 5, 1)->nullable();
            $table->text('note')->nullable();
            $table->timestampsTz();

            $table->unique(['kpi_period_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
        Schema::dropIfExists('kpi_periods');
    }
};
