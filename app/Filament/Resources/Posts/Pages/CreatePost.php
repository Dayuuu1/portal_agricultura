<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = PostResource::class;

    public function getSubheading(): string
    {
        return 'Escriba el título y el contenido, elija la categoría y suba la foto de portada. Puede guardarla como borrador desmarcando «Publicada».';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return PostResource::enforcePublishPermission($data, creating: true);
    }
}
