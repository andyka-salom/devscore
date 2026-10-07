<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SettingKey;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Services\SettingRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Data contoh. Semua akun memakai password "password".
 */
class DatabaseSeeder extends Seeder
{
    public function run(SettingRepository $settings): void
    {
        foreach (SettingKey::cases() as $key) {
            $settings->set($key, $key->defaultValue());
        }

        $year = (int) now()->year;
        foreach ([
            ["{$year}-01-01", 'Tahun Baru Masehi'],
            ["{$year}-05-01", 'Hari Buruh Internasional'],
            ["{$year}-08-17", 'Hari Kemerdekaan RI'],
            ["{$year}-12-25", 'Hari Raya Natal'],
        ] as [$date, $name]) {
            Holiday::query()->create(['date' => $date, 'name' => $name]);
        }

        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\Role::Admin->value]);
        $managerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\Role::Manager->value]);
        $programmerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\Role::Programmer->value]);
        $qaRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\Role::Qa->value]);

        $permissions = [
            'menu-project', 'menu-item', 'menu-available', 'menu-qa', 'menu-approval', 'menu-kpi', 'menu-settings',
            'create-project', 'edit-project', 'delete-project',
            'create-item', 'edit-item', 'delete-item', 'triage-item', 'assign-item', 'claim-item'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        $managerRole->syncPermissions($permissions);
        $adminRole->syncPermissions($permissions);

        $programmerRole->syncPermissions([
            'menu-project', 'menu-item', 'menu-available',
            'edit-item', 'claim-item'
        ]);

        $qaRole->syncPermissions([
            'menu-project', 'menu-item', 'menu-qa',
            'create-item', 'edit-item', 'triage-item'
        ]);

        $admin = User::factory()->admin()->create(['name' => 'Super Admin', 'email' => 'admin@devscore.test']);
        $admin->assignRole($adminRole);

        $manager = User::factory()->manager()->create(['name' => 'Andyka Salom', 'email' => 'andykasalom@gmail.com']);
        $manager->assignRole($managerRole);

        $programmers = collect([
            ['Ilham', 'ilham@gmail.com'],
            ['Raden', 'raden@gmail.com'],
            ['Dika', 'dika@gmail.com'],
        ])->map(function (array $u) use ($programmerRole): User {
            $user = User::factory()->programmer()->create(['name' => $u[0], 'email' => $u[1]]);
            $user->assignRole($programmerRole);
            return $user;
        });

        $qas = collect([
            ['QA', 'qa@gmail.com'],
        ])->map(function (array $u) use ($qaRole): User {
            $user = User::factory()->qa()->create(['name' => $u[0], 'email' => $u[1]]);
            $user->assignRole($qaRole);
            return $user;
        });

    }
}
