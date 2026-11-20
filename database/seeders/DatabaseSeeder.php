<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the portal structure. Content comes from the WordPress import
     * (php artisan portal:importar-wordpress) or from DemoContentSeeder.
     */
    public function run(): void
    {
        ActivityLog::withoutLogging(function (): void {
            User::query()->updateOrCreate(
                ['email' => 'admin@dramdd.gob.pe'],
                ['name' => 'Administrador del Portal', 'password' => env('ADMIN_PASSWORD', 'password'), 'is_admin' => true, 'is_active' => true],
            );

            $this->call([
                SettingSeeder::class,
                OrganizationalUnitSeeder::class,
                CategorySeeder::class,
                DocumentTypeSeeder::class,
                RoleSeeder::class,
                AgencySeeder::class,
                SlideSeeder::class,
                LinkSeeder::class,
                PageSeeder::class,
            ]);
        });
    }
}
