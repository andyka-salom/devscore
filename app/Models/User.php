<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property Role $role
 * @property bool $is_active
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members');
    }

    public function isManager(): bool
    {
        return $this->role === Role::Manager;
    }

    /**
     * Kolom `users.role` adalah satu-satunya sumber peran (satu user satu role, PRD 4).
     * Override HasRoles agar policy tidak bergantung pada tabel model_has_roles Spatie.
     *
     * @param  Role|string|array<array-key, mixed>  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        $roles = is_string($roles) ? explode('|', $roles) : Arr::wrap($roles);

        foreach (Arr::flatten($roles) as $role) {
            if ($this->role === ($role instanceof Role ? $role : Role::tryFrom((string) $role))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Role|string|array<array-key, mixed>  ...$roles
     */
    public function hasAnyRole(...$roles): bool
    {
        return $this->hasRole($roles);
    }
}
