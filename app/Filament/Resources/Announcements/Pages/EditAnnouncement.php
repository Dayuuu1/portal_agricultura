<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Concerns\HasEditHeaderActions;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Support\SiteAnnouncements;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    use HasEditHeaderActions;

    protected static string $resource = AnnouncementResource::class;

    protected static ?string $navigationLabel = 'Editar anuncio';

    public function getSubheading(): string
    {
        return 'Al guardar, el anuncio vuelve a aparecer también a quienes ya lo habían cerrado.';
    }

    /**
     * Staff can always open it on the portal, even disabled or scheduled: only they see it.
     */
    protected function publicUrl(): ?string
    {
        return route('home', [SiteAnnouncements::PREVIEW_PARAMETER => $this->record->getKey()]);
    }
}
