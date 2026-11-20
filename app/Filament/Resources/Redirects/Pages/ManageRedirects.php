<?php

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Resources\Redirects\RedirectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRedirects extends ManageRecords
{
    protected static string $resource = RedirectResource::class;

    public function getSubheading(): string
    {
        return 'Direcciones del portal anterior que llevan a su nueva ubicación, para que los enlaces antiguos (en redes sociales, buscadores o documentos) sigan funcionando.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva redirección'),
        ];
    }
}
