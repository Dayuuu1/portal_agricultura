<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSettings extends ManageRecords
{
    protected static string $resource = SettingResource::class;

    public function getSubheading(): string
    {
        return 'Datos generales del portal: nombre, contacto, redes sociales y enlaces a servicios. Aparecen en la cabecera, el pie de página y la página de contacto. Los vacíos no se muestran.';
    }
}
