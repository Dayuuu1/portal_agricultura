<?php

namespace App\Filament\Resources\DocumentTypes\Pages;

use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDocumentTypes extends ManageRecords
{
    protected static string $resource = DocumentTypeResource::class;

    public function getSubheading(): string
    {
        return 'Tipos de documento y la sección del portal donde aparecen (Normas, Gestión institucional o Información agraria). Un tipo con documentos no se puede eliminar.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo tipo'),
        ];
    }
}
