<?php

namespace Database\Factories;

use App\Models\Slide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slide>
 */
class SlideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'subtitle' => fake()->sentence(12),
            'button_text' => 'Ver más',
            'button_url' => '/noticias',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
