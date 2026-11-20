<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    public function getSubheading(): string
    {
        return 'Categorías para clasificar las noticias. Se muestran como filtros en la sección Noticias del portal.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva categoría'),
        ];
    }
}
