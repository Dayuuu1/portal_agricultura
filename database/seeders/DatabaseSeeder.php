<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the portal structure. Content comes from the WordPress import
     * (php artisan portal:importar-wordpress) or from DemoContentSeeder.
     */
    public function run(): void
    {
        ActivityLog::withoutLogging(function (): void {
            $this->createFirstAdministrator();

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

    /**
     * Creates the first administrator only once: running the seeder again never resets the password of an
     * account already in use. Without ADMIN_PASSWORD a random password is generated and shown once.
     */
    private function createFirstAdministrator(): void
    {
        $email = 'admin@dramdd.gob.pe';

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $password = (string) env('ADMIN_PASSWORD');

        if ($password === '') {
            $password = Str::password(16);
            $this->command?->warn("Se creó {$email} con la contraseña {$password}. Cámbiela al ingresar y active la verificación en dos pasos.");
        }

        User::query()->create(['name' => 'Administrador del Portal', 'email' => $email, 'password' => $password, 'is_admin' => true, 'is_active' => true]);
    }
}
