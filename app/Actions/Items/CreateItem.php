<?php

declare(strict_types=1);

namespace App\Actions\Items;

use App\Enums\ItemStatus;
use App\Enums\Role;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Membuat item baru di backlog. Bila dibuat oleh QA, QA tersebut otomatis menjadi QA item
 * (alur: QA memberi task/bug → Manager triage → programmer claim).
 */
final class CreateItem
{
    /**
     * @param  array{type: string, title: string, description?: string|null, steps_to_reproduce?: string|null, priority: string, due_date?: string|null}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(Project $project, User $creator, array $data): Item
    {
        Gate::forUser($creator)->authorize('create', [Item::class, $project]);

        return DB::transaction(function () use ($project, $creator, $data): Item {
            // Kunci baris project agar nomor item per project tidak bentrok.
            Project::query()->lockForUpdate()->findOrFail($project->getKey());

            $item = new Item([
                ...$data,
                'project_id' => $project->id,
                'created_by' => $creator->id,
                'qa_id' => $creator->hasRole(Role::Qa) ? $creator->id : null,
            ]);
            $item->status = ItemStatus::Backlog;
            $item->save();

            $item->statusLogs()->create([
                'from_status' => null,
                'to_status' => ItemStatus::Backlog,
                'user_id' => $creator->id,
            ]);

            return $item;
        });
    }
}
