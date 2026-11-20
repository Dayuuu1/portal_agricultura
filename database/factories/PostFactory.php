<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => fake()->unique()->sentence(8),
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.implode('</p><p>', fake()->paragraphs(4)).'</p>',
            'is_featured' => false,
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['published_at' => now()->addWeek()]);
    }
}
