<?php

namespace App\Filament\Widgets\Analytics;

use App\Support\Analytics\SiteStatistics;

class Downloads extends RankingWidget
{
    protected function heading(): string
    {
        return 'Documentos más descargados';
    }

    protected function description(): ?string
    {
        return 'Clics en «Descargar» hechos por visitantes (sin robots).';
    }

    protected function itemLabel(): string
    {
        return 'Documento';
    }

    protected function valueLabel(): string
    {
        return 'Descargas';
    }

    protected function rows(): array
    {
        return array_map(fn (array $document): array => [
            'label' => $document['title'],
            'hint' => $document['type'],
            'value' => $document['downloads'],
            'url' => route('documents.show', $document['slug']),
        ], SiteStatistics::downloads($this->period()));
    }
}
