<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\HasEditHeaderActions;
use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use HasEditHeaderActions;

    protected static string $resource = PageResource::class;

    protected function publicUrl(): ?string
    {
        return $this->record->is_published ? route('pages.show', $this->record) : null;
    }

    protected function canDeleteRecord(): bool
    {
        return ! $this->record->isSystemPage();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PageResource::enforcePublishPermission($data, creating: false);
    }
}
