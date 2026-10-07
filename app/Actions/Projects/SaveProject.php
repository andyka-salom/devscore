<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Membuat atau memperbarui project beserta anggota dan link-nya.
 */
final class SaveProject
{
    /**
     * @param  array{code: string, name: string, description?: string|null, status: string, start_date?: string|null, end_date?: string|null, member_ids?: list<int>, links?: list<array{label: string, url: string}>}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(?Project $project, User $actor, array $data): Project
    {
        $project === null
            ? Gate::forUser($actor)->authorize('create', Project::class)
            : Gate::forUser($actor)->authorize('update', $project);

        return DB::transaction(function () use ($project, $actor, $data): Project {
            // Kode project menjadi prefix kode item, jadi hanya diset saat dibuat.
            $project ??= new Project(['created_by' => $actor->id, 'code' => strtoupper($data['code'])]);

            $project->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ])->save();

            $project->members()->sync($data['member_ids'] ?? []);

            $project->links()->delete();
            foreach ($data['links'] ?? [] as $index => $link) {
                $project->links()->create([...$link, 'sort_order' => $index]);
            }

            return $project;
        });
    }
}
