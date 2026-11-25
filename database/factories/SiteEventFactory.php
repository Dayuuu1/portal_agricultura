<?php

namespace Database\Factories;

use App\Enums\SiteEventType;
use App\Models\SiteEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteEvent>
 */
class SiteEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => SiteEventType::Search,
            'label' => fake()->word(),
            'context' => 'portal',
            'value' => fake()->numberBetween(0, 20),
            'date' => now()->format('Y-m-d'),
            'created_at' => now(),
        ];
    }

    public function search(string $term, int $results, string $scope = 'portal'): static
    {
        return $this->state(['type' => SiteEventType::Search, 'label' => $term, 'value' => $results, 'context' => $scope]);
    }
}
