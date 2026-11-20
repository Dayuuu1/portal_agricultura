<?php

namespace App\Filament\Concerns;

/**
 * After creating a record, go back to the list instead of staying on the form.
 */
trait RedirectsToIndex
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
