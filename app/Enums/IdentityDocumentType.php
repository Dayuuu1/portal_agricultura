<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IdentityDocumentType: string implements HasLabel
{
    case Dni = 'dni';
    case CarneExtranjeria = 'ce';
    case Pasaporte = 'pasaporte';
    case Ruc = 'ruc';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dni => 'DNI',
            self::CarneExtranjeria => 'Carné de extranjería',
            self::Pasaporte => 'Pasaporte',
            self::Ruc => 'RUC',
        };
    }

    /**
     * Validation rule for the document number.
     */
    public function numberRule(): string
    {
        return match ($this) {
            self::Dni => 'regex:/^\d{8}$/',
            self::Ruc => 'regex:/^(10|15|17|20)\d{9}$/',
            self::CarneExtranjeria, self::Pasaporte => 'regex:/^[A-Za-z0-9]{6,12}$/',
        };
    }
}
