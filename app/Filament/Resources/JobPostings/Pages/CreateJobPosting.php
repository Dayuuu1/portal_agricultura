<?php

namespace App\Filament\Resources\JobPostings\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\JobPostings\JobPostingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobPosting extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = JobPostingResource::class;

    public function getSubheading(): string
    {
        return 'Registre el código y el puesto. Agregue las bases ahora y, en cada etapa, vuelva a esta convocatoria para sumar los resultados en «Documentos del proceso».';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return JobPostingResource::enforcePublishPermission($data, creating: true);
    }
}
