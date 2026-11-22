<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Life of a file received by the Mesa de Partes Virtual. «Observado» and «No presentado» follow the TUO of
 * Ley N° 27444 (art. 136): the entity cannot refuse a document; it observes it and gives a term to complete it.
 */
enum SubmissionStatus: string implements HasColor, HasIcon, HasLabel
{
    case Recibido = 'recibido';
    case Observado = 'observado';
    case Subsanado = 'subsanado';
    case Derivado = 'derivado';
    case EnAtencion = 'en-atencion';
    case Atendido = 'atendido';
    case NoPresentado = 'no-presentado';
    case Archivado = 'archivado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Recibido => 'Recibido',
            self::Observado => 'Observado',
            self::Subsanado => 'Subsanado',
            self::Derivado => 'Derivado',
            self::EnAtencion => 'En atención',
            self::Atendido => 'Atendido',
            self::NoPresentado => 'No presentado',
            self::Archivado => 'Archivado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Recibido, self::Subsanado => 'info',
            self::Observado => 'warning',
            self::Derivado, self::EnAtencion => 'primary',
            self::Atendido => 'success',
            self::NoPresentado => 'danger',
            self::Archivado => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Recibido => Heroicon::OutlinedInboxArrowDown,
            self::Observado => Heroicon::OutlinedExclamationTriangle,
            self::Subsanado => Heroicon::OutlinedArrowPathRoundedSquare,
            self::Derivado => Heroicon::OutlinedArrowRightCircle,
            self::EnAtencion => Heroicon::OutlinedClock,
            self::Atendido => Heroicon::OutlinedCheckCircle,
            self::NoPresentado => Heroicon::OutlinedXCircle,
            self::Archivado => Heroicon::OutlinedArchiveBox,
        };
    }

    /**
     * What the status means, in the citizen's words.
     */
    public function description(): string
    {
        return match ($this) {
            self::Recibido => 'Su documento fue recibido y la mesa de partes lo está revisando.',
            self::Observado => 'Falta completar o corregir algo. Revise la observación y subsánela dentro del plazo.',
            self::Subsanado => 'Usted envió la subsanación. La mesa de partes la está revisando.',
            self::Derivado => 'Su documento fue admitido y enviado a la oficina que lo atenderá.',
            self::EnAtencion => 'La oficina responsable está atendiendo su documento.',
            self::Atendido => 'Su trámite fue atendido. Revise la respuesta.',
            self::NoPresentado => 'No se subsanó la observación dentro del plazo, por lo que el documento se tuvo por no presentado.',
            self::Archivado => 'El trámite fue archivado.',
        };
    }

    /**
     * Still waiting for something from the entity or the citizen.
     */
    public function isOpen(): bool
    {
        return ! in_array($this, self::closed(), true);
    }

    /**
     * @return list<self>
     */
    public static function closed(): array
    {
        return [self::Atendido, self::NoPresentado, self::Archivado];
    }

    /**
     * Waiting for the mesa de partes to review it.
     *
     * @return list<self>
     */
    public static function toReview(): array
    {
        return [self::Recibido, self::Subsanado];
    }
}
