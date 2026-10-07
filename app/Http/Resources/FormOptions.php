<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\HasLabel;
use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use BackedEnum;

/**
 * Daftar pilihan (select/filter) yang dikirim ke frontend.
 */
final class FormOptions
{
    /**
     * @param  class-string<BackedEnum&HasLabel>  $enum
     * @return list<array{value: string, label: string}>
     */
    public static function enum(string $enum): array
    {
        return array_map(EnumOption::of(...), $enum::cases());
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public static function users(Role $role): array
    {
        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name])
            ->all();
    }

    /**
     * Project yang boleh dipilih user: semua untuk Manager/Admin, selain itu project keanggotaan.
     *
     * @return list<array{value: int, label: string}>
     */
    public static function projects(User $user): array
    {
        $query = $user->hasAnyRole(Role::Manager, Role::Admin)
            ? Project::query()
            : $user->projects()->getQuery();

        return $query
            ->orderBy('name')
            ->get(['projects.id', 'projects.code', 'projects.name'])
            ->map(fn (Project $project): array => [
                'value' => $project->id,
                'label' => "{$project->code} · {$project->name}",
            ])
            ->all();
    }
}
