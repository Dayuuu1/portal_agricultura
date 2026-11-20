<?php

namespace Database\Factories;

use App\Enums\LinkGroup;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => fake()->url(),
            'group' => LinkGroup::EnlacesInteres,
            'show_in_carousel' => true,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function withoutCarousel(): static
    {
        return $this->state(fn (): array => ['show_in_carousel' => false]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
