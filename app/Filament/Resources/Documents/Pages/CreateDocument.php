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
        return 'Suba el archivo, elija el tipo de documento y revise el título. A la derecha verá dónde aparecerá en el portal antes de guardar.';
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
