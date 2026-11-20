<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = PageResource::class;

    public function getSubheading(): string
    {
        return 'La página quedará disponible en /p/su-url-amigable. Comparta ese enlace o pídale al administrador que lo agregue al menú.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PageResource::enforcePublishPermission($data, creating: true);
    }
}
