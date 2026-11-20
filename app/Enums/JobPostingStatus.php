<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JobPostingStatus: string implements HasColor, HasLabel
{
    case EnCurso = 'en-curso';
    case EnEvaluacion = 'en-evaluacion';
    case Finalizada = 'finalizada';
    case Desierta = 'desierta';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnCurso => 'En curso',
            self::EnEvaluacion => 'En evaluación',
            self::Finalizada => 'Finalizada',
            self::Desierta => 'Desierta',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EnCurso => 'success',
            self::EnEvaluacion => 'warning',
            self::Finalizada => 'gray',
            self::Desierta, self::Cancelada => 'danger',
        };
    }

    /**
     * Tailwind classes for the public badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::EnCurso => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
            self::EnEvaluacion => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            self::Finalizada => 'bg-slate-100 text-slate-700 ring-slate-500/20',
            self::Desierta, self::Cancelada => 'bg-red-100 text-red-800 ring-red-600/20',
        };
    }
}
