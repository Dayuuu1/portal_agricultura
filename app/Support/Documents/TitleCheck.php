<?php

namespace App\Support\Documents;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * What staff should know about a title before saving it: whether the document is already on the portal and,
 * for monthly statistics, the month the public page will file it under (it is read from the title).
 */
final readonly class TitleCheck
{
    public function __construct(
        public bool $isDuplicate = false,
        public bool $expectsMonth = false,
        public ?string $period = null,
    ) {}

    public static function for(?string $title, ?DocumentType $type, ?int $year, ?int $ignoreDocumentId = null): self
    {
        $title = trim((string) $title);

        if ($title === '' || $type === null) {
            return new self;
        }

        $isDuplicate = Document::query()
            ->whereBelongsTo($type)
            ->where('title', $title)
            ->when($year, fn ($query) => $query->where('period_year', $year))
            ->when($ignoreDocumentId, fn ($query) => $query->whereKeyNot($ignoreDocumentId))
            ->exists();

        $period = TitlePeriod::fromTitle($title, $year);

        return new self(
            isDuplicate: $isDuplicate,
            expectsMonth: $type->isMonthlyStatistics(),
            period: $period->hasMonth() ? Carbon::create($period->year, $period->month)->translatedFormat('F Y') : null,
        );
    }

    public function needsAttention(): bool
    {
        return $this->isDuplicate || $this->lacksMonth();
    }

    public function lacksMonth(): bool
    {
        return $this->expectsMonth && $this->period === null;
    }

    /**
     * Short status for the review table of the bulk upload.
     */
    public function badge(): string
    {
        return match (true) {
            $this->isDuplicate => 'Ya está en el portal',
            $this->lacksMonth() => 'Sin mes en el título',
            $this->expectsMonth => Str::ucfirst((string) $this->period),
            default => 'Listo',
        };
    }

    public function color(): string
    {
        return $this->needsAttention() ? 'warning' : 'success';
    }

    /**
     * Sentence shown under the title of the single upload form; null when there is nothing to say.
     */
    public function message(): ?string
    {
        return match (true) {
            $this->isDuplicate => 'Ya hay un documento de este tipo y año con el mismo título. Revise que no lo esté subiendo dos veces.',
            $this->lacksMonth() => 'No se reconoce el mes en el título. Agregue el mes y el año (por ejemplo, «… julio 2026») para que el portal lo ubique en su mes.',
            $this->expectsMonth => "En el portal aparecerá entre las publicaciones de {$this->period}.",
            default => null,
        };
    }
}
