<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sample news, documents and job postings for demos. Not run by default:
 * php artisan db:seed --class=DemoContentSeeder
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PostSeeder::class,
            DocumentSeeder::class,
            JobPostingSeeder::class,
        ]);
    }
}
