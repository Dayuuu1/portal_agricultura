<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Citizen;
use App\Models\ProcedureType;
use App\Models\Submission;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fn (): string => Submission::nextCode((int) now()->format('Y')),
            'citizen_id' => Citizen::factory(),
            'procedure_type_id' => ProcedureType::factory(),
            'subject' => 'Solicito '.fake()->words(4, true),
            'description' => fake()->paragraph(),
            'status' => SubmissionStatus::Recibido,
            'submitted_at' => now(),
            'filed_on' => today(),
            'due_on' => today()->addDays(42),
            'folios' => 1,
        ];
    }

    public function observed(?CarbonInterface $deadline = null): static
    {
        return $this->state(fn (): array => [
            'status' => SubmissionStatus::Observado,
            'observation' => 'Falta la firma en el escrito.',
            'observed_at' => now(),
            'cure_deadline' => $deadline ?? today()->addDays(3),
        ]);
    }
}
