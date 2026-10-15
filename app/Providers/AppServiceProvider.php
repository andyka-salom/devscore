<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Item;
use App\Models\KpiPeriod;
use App\Models\Project;
use App\Models\User;
use App\Policies\KpiPolicy;
use App\Services\SettingRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SettingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::policy(KpiPeriod::class, KpiPolicy::class);

        Relation::enforceMorphMap([
            'user' => User::class,
            'item' => Item::class,
            'project' => Project::class,
        ]);
    }
}
