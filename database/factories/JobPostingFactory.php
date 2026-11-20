<?php

namespace Database\Factories;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-2 months', 'now');

        return [
            'code' => 'CAS N° '.fake()->unique()->numerify('0##').'-2026-GOREMAD-GRDE/DRDAR',
            'title' => fake()->jobTitle(),
            'modality' => 'CAS',
            'positions_count' => 1,
            'description' => '<p>'.fake()->paragraph().'</p>',
            'status' => JobPostingStatus::EnCurso,
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+15 days'),
            'is_published' => true,
        ];
    }

    public function status(JobPostingStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
