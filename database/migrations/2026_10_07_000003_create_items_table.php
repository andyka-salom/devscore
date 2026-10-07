<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->unsignedInteger('number');
            $table->string('type', 10);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('steps_to_reproduce')->nullable();
            $table->string('priority', 10)->default('medium');
            $table->unsignedTinyInteger('difficulty')->nullable();
            $table->decimal('estimate_days', 5, 1)->nullable();
            $table->string('status', 20)->default('backlog');
            $table->foreignId('assignee_id')->nullable()->constrained('users');
            $table->foreignId('qa_id')->nullable()->constrained('users');
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->unsignedSmallInteger('qa_fail_count')->default(0);
            $table->unsignedSmallInteger('reject_count')->default(0);
            $table->unsignedSmallInteger('reopen_count')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('approved_at')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['project_id', 'number']);
            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index(['qa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
