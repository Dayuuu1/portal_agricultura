<?php

namespace App\Filament\Resources\JobPostings\Pages;

use App\Filament\Concerns\HasEditHeaderActions;
use App\Filament\Resources\JobPostings\JobPostingResource;
use Filament\Resources\Pages\EditRecord;

class EditJobPosting extends EditRecord
{
    use HasEditHeaderActions;

    protected static string $resource = JobPostingResource::class;

    public function getSubheading(): string
    {
        return 'Para publicar un nuevo resultado, agréguelo al final de «Documentos del proceso» y actualice el estado si corresponde.';
    }

    protected function publicUrl(): ?string
    {
        return $this->record->is_published ? route('job-postings.show', $this->record) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return JobPostingResource::enforcePublishPermission($data, creating: false);
    }
}
