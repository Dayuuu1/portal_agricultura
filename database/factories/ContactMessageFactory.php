<?php

namespace Database\Factories;

use App\Enums\ContactMessageType;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => ContactMessageType::Consulta,
            'full_name' => fake()->name(),
            'document_number' => fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('9########'),
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(),
        ];
    }
}
