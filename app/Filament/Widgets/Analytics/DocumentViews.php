<?php

namespace App\Filament\Widgets\Analytics;

use App\Enums\SiteSection;
use App\Support\Analytics\SiteStatistics;

class DocumentViews extends RankingWidget
{
    protected function heading(): string
    {
        return 'Documentos más consultados';
    }

    protected function description(): ?string
    {
        return 'Fichas de documentos abiertas en el portal.';
    }

    protected function itemLabel(): string
    {
        return 'Documento';
    }

    protected function valueLabel(): string
    {
        return 'Vistas';
    }

    protected function rows(): array
    {
        return array_map(fn (array $page): array => [
            'label' => $page['title'] ?? $page['path'],
            'hint' => SiteStatistics::formatNumber($page['visitors']).' visitantes · '.SiteStatistics::formatDuration($page['duration']).' de media',
            'value' => $page['views'],
            'url' => url($page['path']),
        ], SiteStatistics::topPages($this->period(), section: SiteSection::Documents, detailPagesOnly: true));
    }
}
