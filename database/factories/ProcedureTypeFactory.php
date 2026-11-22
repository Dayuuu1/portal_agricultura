<?php

namespace Database\Factories;

use App\Models\ProcedureType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcedureType>
 */
class ProcedureTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Trámite '.fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'requirements' => "Escrito firmado\nCopia del documento de identidad",
            'response_days' => 30,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }

    public function withFee(float $fee = 25.5): static
    {
        return $this->state(fn (): array => ['fee' => $fee, 'requires_payment_proof' => true]);
    }
}
