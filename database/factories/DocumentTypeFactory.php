<?php

namespace Database\Factories;

use App\Enums\DocumentSection;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'section' => DocumentSection::Normativa,
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }

    public function section(DocumentSection $section): static
    {
        return $this->state(fn (): array => ['section' => $section]);
    }
}
