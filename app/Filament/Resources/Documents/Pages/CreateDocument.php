<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = DocumentResource::class;

    public function getSubheading(): string
    {
        return 'Suba el archivo (PDF, Word o Excel, máximo 20 MB) y elija el tipo de documento: el tipo define en qué sección del portal aparecerá.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DocumentResource::enforcePublishPermission($data, creating: true);
    }
}
