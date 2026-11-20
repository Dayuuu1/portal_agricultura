<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function getSubheading(): string
    {
        return 'Páginas de contenido libre (Nosotros, Código de ética, Política de privacidad…). Las páginas del sistema no se pueden eliminar porque el portal las enlaza.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva página'),
        ];
    }
}
