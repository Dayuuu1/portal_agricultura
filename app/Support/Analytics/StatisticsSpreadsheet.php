<?php

namespace App\Support\Analytics;

use App\Enums\SiteEventType;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Excel workbook of «Estadísticas del portal»: a summary sheet and one sheet per list, with real numbers, dates,
 * percentages and times (so they can be sorted, filtered and charted) under an institutional heading.
 * Written with OpenSpout, which Filament already installs for its own exports.
 */
final class StatisticsSpreadsheet
{
    private const HEADER_ROW = 6;

    private const GREEN = '1C7C45';

    private const DARK_GREEN = '0E3B25';

    private StatisticsReport $previous;

    public function __construct(
        private readonly StatisticsReport $report,
        private readonly string $generatedBy,
    ) {
        $this->previous = new StatisticsReport($report->period->previous());
    }

    public function fileName(): string
    {
        return 'estadisticas-portal-'.$this->report->period->from().'-al-'.$this->report->period->to().'.xlsx';
    }

    /**
     * Writes the workbook to a temporary file and returns its path.
     */
    public function store(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'estadisticas');
        $writer = new Writer;
        $writer->openToFile($path);

        foreach ($this->sheets() as $index => $sheet) {
            $current = $index === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $current->setName($sheet['name']);
            $current->setSheetView((new SheetView)->setFreezeRow(self::HEADER_ROW + 1));

            foreach ($sheet['columns'] as $column => [, $width]) {
                $current->setColumnWidth($width, $column + 1);
            }

            $this->writeHeading($writer, $sheet['title']);
            $writer->addRow(Row::fromValues(array_column($sheet['columns'], 0), $this->headerStyle()));

            if ($sheet['rows'] === []) {
                $writer->addRow(Row::fromValues(['Sin datos en el periodo elegido.'], (new Style)->setFontItalic()->setFontColor('64748B')));

                continue;
            }

            $styles = array_map(fn (array $column): Style => $this->cellStyle($column[2] ?? null), $sheet['columns']);

            foreach ($sheet['rows'] as $row) {
                $writer->addRow(Row::fromValuesWithStyles($row, null, $styles));
            }
        }

        $writer->close();

        return $path;
    }

    private function writeHeading(Writer $writer, string $title): void
    {
        $period = $this->report->period;

        $writer->addRow(Row::fromValues(['Estadísticas del portal · '.$title], (new Style)->setFontBold()->setFontSize(14)->setFontColor(self::DARK_GREEN)));
        $writer->addRow(Row::fromValues([Setting::valueOf('site_name') ?? config('app.name')], (new Style)->setFontColor('475569')));
        $writer->addRow(Row::fromValues(['Periodo: '.$period->label().' · comparado con: '.Str::lcfirst($period->previous()->label())], (new Style)->setFontColor('475569')));
        $writer->addRow(Row::fromValues(['Generado el '.now()->format('d/m/Y').' a las '.now()->format('H:i').' por '.$this->generatedBy.'. Cifras propias del portal, sin datos personales.'], (new Style)->setFontColor('94A3B8')->setFontSize(9)));
        $writer->addRow(Row::fromValues([]));
    }

    private function headerStyle(): Style
    {
        return (new Style)->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor(self::GREEN)->setShouldWrapText();
    }

    private function cellStyle(?string $format): Style
    {
        $style = new Style;

        return match ($format) {
            'number' => $style->setFormat('#,##0'),
            'decimal' => $style->setFormat('#,##0.0'),
            'percent' => $style->setFormat('0.0%'),
            'change' => $style->setFormat('+0.0%;-0.0%;0.0%'),
            'time' => $style->setFormat('[m]:ss')->setCellAlignment(CellAlignment::RIGHT),
            'date' => $style->setFormat('dd/mm/yyyy'),
            default => $style,
        };
    }

    /**
     * Seconds as an Excel time (fraction of a day), shown as minutes:seconds.
     */
    private static function time(?int $seconds): ?float
    {
        return $seconds === null ? null : $seconds / 86400;
    }

    private static function share(float $percent): float
    {
        return $percent / 100;
    }

    /**
     * Each sheet: name (max. 31 characters), title, columns as [header, width, format] and rows of values.
     *
     * @return list<array{name: string, title: string, columns: list<array{0: string, 1: float, 2?: string}>, rows: list<list<mixed>>}>
     */
    private function sheets(): array
    {
        $limit = StatisticsReport::EXPORT_LIMIT;
        $report = $this->report;
        $singleDay = $report->period->isSingleDay();

        return [
            [
                'name' => 'Resumen',
                'title' => 'Resumen',
                'columns' => [['Indicador', 38], ['Periodo analizado', 20], ['Periodo anterior', 20], ['Variación', 14, 'change']],
                'rows' => $this->summaryRows(),
            ],
            [
                'name' => $singleDay ? 'Por hora' : 'Por día',
                'title' => $singleDay ? 'Visitas por hora' : 'Visitas por día',
                'columns' => [[$singleDay ? 'Hora' : 'Fecha', 14, $singleDay ? null : 'date'], ['Visitantes', 14, 'number'], ['Páginas vistas', 16, 'number']],
                'rows' => array_map(
                    fn (string $key, array $values): array => [$singleDay ? str_pad($key, 2, '0', STR_PAD_LEFT).':00' : CarbonImmutable::parse($key), $values['visitors'], $values['views']],
                    array_keys($report->trend()),
                    $report->trend(),
                ),
            ],
            [
                'name' => 'Páginas',
                'title' => 'Páginas más vistas',
                'columns' => [['Página', 60], ['Dirección', 45], ['Vistas', 12, 'number'], ['Visitantes', 12, 'number'], ['Tiempo medio (min:s)', 16, 'time']],
                'rows' => array_map(fn (array $page): array => [$page['title'] ?? $page['path'], $page['path'], $page['views'], $page['visitors'], self::time($page['duration'])], $report->pages($limit)),
            ],
            [
                'name' => 'Secciones',
                'title' => 'Secciones del portal',
                'columns' => [['Sección', 30], ['Vistas', 12, 'number'], ['Visitantes', 12, 'number'], ['% de las vistas', 16, 'percent']],
                'rows' => array_map(fn (array $section): array => [$section['label'], $section['views'], $section['visitors'], self::share($section['share'])], $report->sections()),
            ],
            [
                'name' => 'Origen del tráfico',
                'title' => 'De dónde llegan las visitas',
                'columns' => [['Origen', 34], ['Visitas', 12, 'number'], ['% de las visitas', 16, 'percent'], ['Sitio principal', 30]],
                'rows' => array_map(fn (array $source): array => [$source['label'], $source['visits'], self::share($source['share']), $source['topSite']], $report->sources()),
            ],
            [
                'name' => 'Sitios de referencia',
                'title' => 'Sitios que envían visitas',
                'columns' => [['Sitio', 40], ['Visitas', 12, 'number']],
                'rows' => array_map(fn (string $site, int $visits): array => [$site, $visits], array_keys($referrers = $report->referrers($limit)), $referrers),
            ],
            ...array_map(fn (array $technology): array => [
                'name' => $technology[0],
                'title' => $technology[0],
                'columns' => [[$technology[1], 28], ['Visitantes', 12, 'number'], ['% de los visitantes', 18, 'percent']],
                'rows' => array_map(fn (array $row): array => [$row['label'], $row['visitors'], self::share($row['share'])], $report->technology($technology[2])),
            ], [['Dispositivos', 'Dispositivo', 'device'], ['Navegadores', 'Navegador', 'browser'], ['Sistemas operativos', 'Sistema', 'os']]),
            [
                'name' => 'Horas',
                'title' => 'Páginas vistas por hora del día (hora de Perú)',
                'columns' => [['Hora', 12], ['Páginas vistas', 16, 'number'], ['% del día', 12, 'percent']],
                'rows' => array_map(
                    fn (int $hour, int $views): array => [str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00', $views, array_sum($report->hours()) > 0 ? $views / array_sum($report->hours()) : 0],
                    array_keys($report->hours()),
                    $report->hours(),
                ),
            ],
            [
                'name' => 'Búsquedas',
                'title' => 'Lo que más buscan',
                'columns' => [['Término buscado', 40], ['Búsquedas', 12, 'number'], ['Resultados', 12, 'number'], ['Dónde se buscó', 32]],
                'rows' => array_map(fn (array $search): array => [$search['term'], $search['searches'], $search['results'], $search['scopes']], $report->searches(limit: $limit)),
            ],
            [
                'name' => 'Búsquedas sin resultados',
                'title' => 'Búsquedas sin resultados (contenido que no encuentran)',
                'columns' => [['Término buscado', 40], ['Búsquedas', 12, 'number'], ['Dónde se buscó', 32]],
                'rows' => array_map(fn (array $search): array => [$search['term'], $search['searches'], $search['scopes']], $report->searches(withoutResults: true, limit: $limit)),
            ],
            [
                'name' => 'Documentos consultados',
                'title' => 'Documentos más consultados',
                'columns' => [['Documento', 60], ['Dirección', 45], ['Vistas', 12, 'number'], ['Visitantes', 12, 'number'], ['Tiempo medio (min:s)', 16, 'time']],
                'rows' => array_map(fn (array $page): array => [$page['title'] ?? $page['path'], $page['path'], $page['views'], $page['visitors'], self::time($page['duration'])], $report->documentViews($limit)),
            ],
            [
                'name' => 'Descargas',
                'title' => 'Documentos más descargados',
                'columns' => [['Documento', 60], ['Tipo de documento', 30], ['Descargas', 12, 'number']],
                'rows' => array_map(fn (array $document): array => [$document['title'], $document['type'], $document['downloads']], $report->downloads($limit)),
            ],
            [
                'name' => 'Enlaces externos',
                'title' => 'Enlaces a otros sitios',
                'columns' => [['Sitio', 40], ['Clics', 12, 'number']],
                'rows' => array_map(fn (string $site, int $clicks): array => [$site, $clicks], array_keys($outbound = $report->outboundLinks($limit)), $outbound),
            ],
            [
                'name' => 'Direcciones no encontradas',
                'title' => 'Direcciones que no existen (error 404)',
                'columns' => [['Dirección', 60], ['Visitas', 12, 'number']],
                'rows' => array_map(fn (string $path, int $visits): array => [$path, $visits], array_keys($notFound = $report->notFound($limit)), $notFound),
            ],
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    private function summaryRows(): array
    {
        $rows = array_map(fn (array $indicator): array => [
            $indicator['label'],
            $indicator['value'],
            $indicator['previousValue'],
            $indicator['change'] === null ? null : $indicator['change'] / 100,
        ], $this->report->indicators());

        $totals = [
            'Búsquedas realizadas' => fn (StatisticsReport $report): int => $report->searchTotals()['searches'],
            'Búsquedas sin resultados' => fn (StatisticsReport $report): int => $report->searchTotals()['withoutResults'],
            'Descargas de documentos' => fn (StatisticsReport $report): int => $report->eventTotal(SiteEventType::Download),
            'Clics a sitios externos' => fn (StatisticsReport $report): int => $report->eventTotal(SiteEventType::Outbound),
            'Visitas a direcciones inexistentes' => fn (StatisticsReport $report): int => $report->eventTotal(SiteEventType::NotFound),
        ];

        foreach ($totals as $label => $total) {
            $current = $total($this->report);
            $previous = $total($this->previous);
            $change = SiteStatistics::change($current, $previous);

            $rows[] = [$label, SiteStatistics::formatNumber($current), SiteStatistics::formatNumber($previous), $change === null ? null : $change / 100];
        }

        return $rows;
    }
}
