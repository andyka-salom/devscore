<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\FeatureRequestStatus;
use App\Http\Resources\EnumOption;
use App\Models\FeatureRequest;
use App\Models\Item;
use App\Models\KpiPeriod;
use App\Models\User;
use App\Queries\ItemListQuery;
use App\Queries\ItemQueueQuery;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => EnumOption::of($user->role),
                ],
            ],
            'nav' => $user === null ? null : fn (): array => $this->navigation($user),
        ];
    }

    /**
     * Menu yang boleh diakses + angka badge. null = menu disembunyikan (tidak berwenang).
     *
     * @return array<string, int|bool|null>
     */
    private function navigation(User $user): array
    {
        return [
            'approval' => $user->can('viewApprovalQueue', Item::class) ? ItemQueueQuery::approval()->count() : null,
            'qa' => $user->can('viewQaQueue', Item::class) ? ItemQueueQuery::qa($user)->count() : null,
            'available' => $user->can('viewAvailable', Item::class)
                ? (new ItemListQuery($user))->availableQuery()->count()
                : null,
            // Manager: jumlah yang menunggu persetujuan; peran lain: tidak ada badge.
            'featureRequests' => $user->can('viewAny', FeatureRequest::class)
                ? ($user->can('viewReviewQueue', FeatureRequest::class)
                    ? FeatureRequest::query()->where('status', FeatureRequestStatus::Pending)->count()
                    : true)
                : null,
            'kpi' => $user->can('viewAny', KpiPeriod::class),
            'settings' => $user->can('viewSettings', KpiPeriod::class),
        ];
    }
}
