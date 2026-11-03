<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('scenario');
            $table->text('steps')->nullable();
            $table->text('expected_result');
            $table->text('actual_result')->nullable();
            $table->string('result', 20)->default('untested');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('tested_by')->nullable()->constrained('users');
            $table->timestampTz('tested_at')->nullable();
            $table->timestampsTz();

            $table->index(['item_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_cases');
    }
};
