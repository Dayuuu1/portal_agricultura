<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * What kind of message an announcement is: sets the label, icon and accent colour of the window.
 */
enum AnnouncementKind: string implements HasColor, HasIcon, HasLabel
{
    case Notice = 'notice';
    case Alert = 'alert';
    case Event = 'event';
    case JobPosting = 'job_posting';
    case Highlight = 'highlight';

    public function getLabel(): string
    {
        return match ($this) {
            self::Notice => 'Comunicado',
            self::Alert => 'Aviso importante',
            self::Event => 'Evento',
            self::JobPosting => 'Convocatoria',
            self::Highlight => 'Destacado',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Notice => Heroicon::OutlinedMegaphone,
            self::Alert => Heroicon::OutlinedExclamationTriangle,
            self::Event => Heroicon::OutlinedCalendarDays,
            self::JobPosting => Heroicon::OutlinedBriefcase,
            self::Highlight => Heroicon::OutlinedSparkles,
        };
    }

    public function getColor(): string
    {
        return $this->isGold() ? 'warning' : 'primary';
    }

    /**
     * Blade icon component name for the portal («heroicon-o-megaphone»).
     */
    public function iconComponent(): string
    {
        return 'heroicon-'.$this->getIcon()->value;
    }

    /**
     * Alerts and highlights use the institutional gold so they stand apart from routine notices.
     */
    public function isGold(): bool
    {
        return in_array($this, [self::Alert, self::Highlight], true);
    }

    /**
     * Tailwind classes for the public badge.
     */
    public function badgeClasses(): string
    {
        return $this->isGold()
            ? 'bg-gold-100 text-brand-950 ring-gold-400/60'
            : 'bg-brand-50 text-brand-800 ring-brand-200';
    }
}
