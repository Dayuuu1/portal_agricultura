<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Str;

/**
 * Spanish capitalizes only the first word ("Tipos de documento", not "Tipos De Documento").
 */
trait HasSpanishLabels
{
    /**
     * Used inside titles such as "Crear :label", so it stays lowercase ("Crear noticia").
     */
    public static function getTitleCaseModelLabel(): string
    {
        return static::getModelLabel();
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
