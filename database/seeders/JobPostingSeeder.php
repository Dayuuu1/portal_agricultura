<?php

namespace Database\Seeders;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class JobPostingSeeder extends Seeder
{
    public function run(): void
    {
        $personal = OrganizationalUnit::query()->where('name', 'Unidad de Personal')->first();

        $postings = [
            ['CAS N° 003-2026-GOREMAD-GRDE/DRDAR', 'Técnico agropecuario (convocatoria de ejemplo)', 'CAS', JobPostingStatus::EnCurso, '2026-11-10', '2026-11-28',
                ['Bases del proceso']],
            ['CAS por suplencia N° 002-2026-GOREMAD-GRDE/DRDAR', 'Contratación por suplencia', 'CAS por suplencia', JobPostingStatus::Finalizada, '2026-05-04', '2026-05-22',
                ['Bases del proceso', 'Resultado de evaluación curricular', 'Resultado final']],
            ['CAS por suplencia N° 001-2026-GOREMAD-GRDE/DRDAR', 'Contratación por suplencia', 'CAS por suplencia', JobPostingStatus::Finalizada, '2026-03-02', '2026-03-20',
                ['Bases del proceso', 'Resultado final']],
            ['CAS N° 002-2024-GOREMAD-GRDE/DRA', 'Contratación administrativa de servicios', 'CAS', JobPostingStatus::Finalizada, '2024-06-03', '2024-06-21',
                ['Bases del proceso', 'Resultado final']],
            ['CAS N° 002-2022-GOREMAD-GRDE/DRA', 'Contratación administrativa de servicios', 'CAS', JobPostingStatus::Finalizada, '2022-04-04', '2022-04-22',
                ['Bases del proceso', 'Resultado final']],
        ];

        foreach ($postings as [$code, $title, $modality, $status, $startsAt, $endsAt, $files]) {
            $posting = JobPosting::query()->updateOrCreate(
                ['code' => $code],
                [
                    'organizational_unit_id' => $personal?->id,
                    'title' => $title,
                    'modality' => $modality,
                    'status' => $status,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'description' => '<p>Proceso de selección para la contratación de personal bajo el régimen del Decreto Legislativo N° 1057. Revise las bases y los resultados publicados en cada etapa.</p>',
                    'is_published' => true,
                ],
            );

            $posting->files()->delete();

            foreach ($files as $order => $label) {
                $posting->files()->create([
                    'label' => $label,
                    'file_path' => DocumentSeeder::SAMPLE_PDF,
                    'published_at' => $startsAt,
                    'sort_order' => $order,
                ]);
            }
        }
    }
}
