<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Enums\SettingKey;
use App\Models\KpiPeriod;
use App\Models\User;
use App\Services\SettingRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class UpdateSettings
{
    public function __construct(private readonly SettingRepository $settings) {}

    /**
     * @param  array<string, mixed>  $values  kunci = SettingKey value, sudah tervalidasi
     *
     * @throws AuthorizationException
     */
    public function handle(User $actor, array $values): void
    {
        Gate::forUser($actor)->authorize('manageSettings', KpiPeriod::class);

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                $this->settings->set(SettingKey::from($key), $value);
            }
        });
    }
}
