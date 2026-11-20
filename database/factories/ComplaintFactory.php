<?php

namespace Database\Factories;

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Enums\IdentityDocumentType;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'document_type' => IdentityDocumentType::Dni,
            'document_number' => fake()->numerify('########'),
            'address' => fake()->streetAddress().', Puerto Maldonado',
            'phone' => fake()->numerify('9########'),
            'email' => fake()->safeEmail(),
            'incident_date' => fake()->dateTimeBetween('-10 days'),
            'reason' => fake()->randomElement(ComplaintReason::cases()),
            'description' => fake()->paragraph(),
            'notify_by_email' => true,
            'status' => ComplaintStatus::Registrado,
        ];
    }

    public function answered(ComplaintStatus $status = ComplaintStatus::Aceptado): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'response' => fake()->paragraph(),
            'responded_at' => now(),
        ]);
    }
}
