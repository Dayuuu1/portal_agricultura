<?php

namespace Database\Factories;

use App\Models\JobPosting;
use App\Models\JobPostingFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPostingFile>
 */
class JobPostingFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'label' => fake()->randomElement(['Bases del proceso', 'Resultado de evaluación curricular', 'Resultado final']),
            'file_path' => 'convocatorias/demo.pdf',
            'published_at' => now(),
            'sort_order' => 0,
        ];
    }
}
