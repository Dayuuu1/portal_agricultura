<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\Post;
use App\Support\MonthlyCounts;
use Filament\Widgets\ChartWidget;

class PublicationsChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Publicaciones de los últimos 12 meses';

    protected ?string $description = 'Noticias por fecha de publicación y documentos por fecha de emisión.';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $posts = MonthlyCounts::for(Post::query()->published(), 'published_at');
        $documents = MonthlyCounts::for(Document::query()->published(), 'issued_at');

        return [
            'datasets' => [
                [
                    'label' => 'Noticias',
                    'data' => array_values($posts),
                    'backgroundColor' => '#1c7c45',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Documentos',
                    'data' => array_values($documents),
                    'backgroundColor' => '#ebb43a',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => MonthlyCounts::labels($posts),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
