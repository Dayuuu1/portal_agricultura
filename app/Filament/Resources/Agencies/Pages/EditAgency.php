<?php

namespace App\Filament\Resources\Agencies\Pages;

use App\Filament\Concerns\HasEditHeaderActions;
use App\Filament\Resources\Agencies\AgencyResource;
use Filament\Resources\Pages\EditRecord;

class EditAgency extends EditRecord
{
    use HasEditHeaderActions;

    protected static string $resource = AgencyResource::class;

    protected function publicUrl(): ?string
    {
        return $this->record->is_active ? route('agencies.show', $this->record) : null;
    }
}
