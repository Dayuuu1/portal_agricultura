<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\HasEditHeaderActions;
use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    use HasEditHeaderActions;

    protected static string $resource = PostResource::class;

    public function getSubheading(): string
    {
        return 'Los cambios se verán en el portal en cuanto guarde.';
    }

    protected function publicUrl(): ?string
    {
        return $this->record->is_published && $this->record->published_at?->isPast()
            ? route('posts.show', $this->record)
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PostResource::enforcePublishPermission($data, creating: false);
    }
}
