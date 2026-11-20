<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageActivityLogs extends ManageRecords
{
    protected static string $resource = ActivityLogResource::class;

    public function getSubheading(): string
    {
        return 'Historial de lo que se hace en el panel: quién creó, modificó o eliminó cada registro, y los inicios de sesión (incluidos los intentos fallidos). Use «Ver» para revisar el detalle de los cambios.';
    }
}
