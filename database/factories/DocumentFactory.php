<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::factory(),
            'title' => fake()->unique()->sentence(6),
            'code' => 'RD N° '.fake()->numerify('###').'-2026-GOREMAD/GRDE/DRDAR',
            'description' => fake()->sentence(12),
            'issued_at' => fake()->dateTimeBetween('-1 year'),
            'is_published' => true,
        ];
    }

    public function withFile(string $path = 'documentos/demo.pdf'): static
    {
        return $this->state(fn (): array => ['file_path' => $path, 'file_name' => basename($path)]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
