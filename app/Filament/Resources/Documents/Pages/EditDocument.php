<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Concerns\HasEditHeaderActions;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    use HasEditHeaderActions;

    protected static string $resource = DocumentResource::class;

    public function getSubheading(): string
    {
        return 'Si reemplaza el archivo, el enlace de descarga del portal se mantiene igual.';
    }

    protected function publicUrl(): ?string
    {
        return $this->record->is_published ? route('documents.show', $this->record) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return DocumentResource::enforcePublishPermission($data, creating: false);
    }
}
