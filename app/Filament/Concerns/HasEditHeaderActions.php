<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;

/**
 * Edit pages show a "view on the public site" shortcut next to the delete button.
 */
trait HasEditHeaderActions
{
    abstract protected function publicUrl(): ?string;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewOnSite')
                ->label('Ver en el portal')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): ?string => $this->publicUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->publicUrl() !== null),
            DeleteAction::make()
                ->visible(fn (): bool => $this->canDeleteRecord()),
        ];
    }

    protected function canDeleteRecord(): bool
    {
        return true;
    }
}
