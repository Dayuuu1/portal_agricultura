<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = AnnouncementResource::class;

    public function getSubheading(): string
    {
        return 'Elija el tipo, escriba un título breve y, si quiere, una imagen o una noticia para destacar. La vista previa muestra la ventana tal como la verá el visitante.';
    }
}
