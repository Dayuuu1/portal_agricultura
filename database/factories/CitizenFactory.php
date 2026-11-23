<?php

namespace Database\Factories;

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Models\Citizen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citizen>
 */
class CitizenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_type' => PersonType::Natural,
            'document_type' => IdentityDocumentType::Dni,
            'document_number' => fake()->unique()->numerify('########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName().' '.fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->numerify('9########'),
            'department' => 'Madre de Dios',
            'province' => 'Tambopata',
            'district' => 'Tambopata',
            'address' => fake()->streetAddress(),
            'password' => 'Secreta123456',
            'privacy_accepted_at' => now(),
            'truthfulness_declared_at' => now(),
            'e_notifications_authorized_at' => now(),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function withoutElectronicNotifications(): static
    {
        return $this->state(fn (): array => ['e_notifications_authorized_at' => null]);
    }

    public function identityVerified(): static
    {
        return $this->state(fn (): array => ['identity_verified_at' => now()]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
