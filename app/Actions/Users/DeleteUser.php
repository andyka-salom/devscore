<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Menghapus user yang belum punya riwayat. User dengan riwayat (item, log status, notes, KPI)
 * harus dinonaktifkan agar data historis & KPI tetap utuh.
 */
final class DeleteUser
{
    /**
     * Tabel & kolom yang mereferensikan users.id tanpa cascade.
     *
     * @var array<string, list<string>>
     */
    private const array REFERENCES = [
        'items' => ['assignee_id', 'qa_id', 'created_by'],
        'item_status_logs' => ['user_id'],
        'notes' => ['user_id'],
        'projects' => ['created_by'],
        'kpi_periods' => ['closed_by'],
        'kpi_snapshots' => ['user_id'],
        'feature_requests' => ['requested_by', 'reviewed_by'],
    ];

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handle(User $user, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $user);

        if ($this->hasHistory($user)) {
            throw ValidationException::withMessages([
                'user' => "{$user->name} sudah memiliki riwayat kerja dan tidak bisa dihapus. Nonaktifkan user ini saja.",
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->projects()->detach();
            $user->delete();
        });
    }

    public function hasHistory(User $user): bool
    {
        foreach (self::REFERENCES as $table => $columns) {
            $exists = DB::table($table)
                ->where(function ($query) use ($columns, $user): void {
                    foreach ($columns as $column) {
                        $query->orWhere($column, $user->id);
                    }
                })
                ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }
}
