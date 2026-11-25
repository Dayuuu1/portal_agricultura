<?php

namespace App\Filament\Pages;

use App\Enums\SiteEventType;
use App\Filament\Widgets\Analytics\Devices;
use App\Filament\Widgets\Analytics\DocumentViews;
use App\Filament\Widgets\Analytics\Downloads;
use App\Filament\Widgets\Analytics\Hours;
use App\Filament\Widgets\Analytics\LiveVisitors;
use App\Filament\Widgets\Analytics\NotFoundPages;
use App\Filament\Widgets\Analytics\OutboundLinks;
use App\Filament\Widgets\Analytics\SearchesWithoutResults;
use App\Filament\Widgets\Analytics\SearchTerms;
use App\Filament\Widgets\Analytics\Sections;
use App\Filament\Widgets\Analytics\SiteOverview;
use App\Filament\Widgets\Analytics\Technology;
use App\Filament\Widgets\Analytics\TopPages;
use App\Filament\Widgets\Analytics\TrafficSources;
use App\Filament\Widgets\Analytics\VisitorsTrend;
use App\Models\PageView;
use App\Models\User;
use App\Support\Analytics\AnalyticsPeriod;
use App\Support\Analytics\PageViewRecorder;
use App\Support\Analytics\StatisticsReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Statistics of the public site, collected by the portal itself (App\Support\Analytics): visitors, time spent,
 * where they come from, what they read, search and download.
 *
 * Layout: the period filter, then what matters at a glance (who is online now and the key indicators), then one tab
 * per question (visits, content, searches, documents, links and errors). Reports export from the header.
 */
class SiteAnalytics extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'estadisticas';

    protected static ?string $title = 'Estadísticas del portal';

    protected static ?string $navigationLabel = 'Estadísticas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?int $navigationSort = -1;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasPermission('estadisticas.view');
    }

    public function getSubheading(): string
    {
        return 'Cómo usa la ciudadanía el portal: cuántos lo visitan, cuánto se quedan, de dónde llegan y qué buscan y descargan. '
            .'Cifras propias del portal, sin cookies ni servicios externos; se conservan '.PageView::RETENTION_MONTHS.' meses.';
    }

    private function period(): AnalyticsPeriod
    {
        return AnalyticsPeriod::fromFilters($this->filters);
    }

    /**
     * The chosen period as query string, so the exported report covers exactly what is on screen.
     *
     * @return array<string, string>
     */
    private function periodQuery(): array
    {
        return array_filter([
            'period' => (string) ($this->filters['period'] ?? AnalyticsPeriod::DEFAULT),
            'start' => (string) ($this->filters['start'] ?? ''),
            'end' => (string) ($this->filters['end'] ?? ''),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportExcel')
                    ->label('Excel (.xlsx)')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->tooltip('Libro con un resumen y una hoja por informe, con las listas completas.')
                    ->url(fn (): string => route('admin.statistics.export', $this->periodQuery())),
                Action::make('printReport')
                    ->label('Informe para imprimir o PDF')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->tooltip('Informe en hoja A4 con encabezado institucional, listo para imprimir o guardar como PDF.')
                    ->url(fn (): string => route('admin.statistics.report', $this->periodQuery()))
                    ->openUrlInNewTab(),
            ])
                ->label('Exportar informe')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->button()
                ->dropdownPlacement('bottom-end'),
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            // The filters schema is a 2–4 column grid of its own: the bar spans all of it.
            Section::make()
                ->compact()
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                ->schema([
                    Select::make('period')
                        ->label('Periodo')
                        ->options(AnalyticsPeriod::OPTIONS)
                        ->default(AnalyticsPeriod::DEFAULT)
                        ->selectablePlaceholder(false)
                        ->live(),
                    DatePicker::make('start')
                        ->label('Desde')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->live()
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    DatePicker::make('end')
                        ->label('Hasta')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->afterOrEqual('start')
                        ->live()
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    Text::make(function (Get $get): string {
                        $period = AnalyticsPeriod::fromFilters(['period' => $get('period'), 'start' => $get('start'), 'end' => $get('end')]);

                        return $period->label().' ('.$period->days().' '.Str::plural('día', $period->days()).'), comparado con '.Str::lcfirst($period->previous()->label()).'.';
                    })
                        ->color('gray')
                        ->columnSpan(fn (Get $get): array => $get('period') === 'custom' ? ['default' => 1, 'md' => 2, 'xl' => 1] : ['default' => 1, 'md' => 1, 'xl' => 3])
                        ->extraAttributes(['class' => 'self-end pb-2']),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        $report = new StatisticsReport($this->period());
        $withoutResults = $report->searchTotals()['withoutResults'];
        $notFound = $report->eventTotal(SiteEventType::NotFound);

        return $schema->components([
            $this->getFiltersFormContentComponent(),
            $this->widgetGrid([LiveVisitors::class, SiteOverview::class]),
            Tabs::make('Informes')
                ->contained(false)
                ->persistTabInQueryString('informe')
                ->tabs([
                    Tab::make('Visitas y audiencia')
                        ->icon(Heroicon::OutlinedUsers)
                        ->schema([$this->widgetGrid([VisitorsTrend::class, TrafficSources::class, Devices::class, Hours::class, Technology::class])]),
                    Tab::make('Contenido')
                        ->icon(Heroicon::OutlinedNewspaper)
                        ->schema([$this->widgetGrid([TopPages::class, Sections::class])]),
                    Tab::make('Búsquedas')
                        ->icon(Heroicon::OutlinedMagnifyingGlass)
                        ->badge($withoutResults > 0 ? $withoutResults : null)
                        ->badgeColor('warning')
                        ->badgeTooltip('Búsquedas sin resultados en el periodo')
                        ->schema([$this->widgetGrid([SearchTerms::class, SearchesWithoutResults::class])]),
                    Tab::make('Documentos')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->schema([$this->widgetGrid([DocumentViews::class, Downloads::class])]),
                    Tab::make('Enlaces y errores')
                        ->icon(Heroicon::OutlinedLink)
                        ->badge($notFound > 0 ? $notFound : null)
                        ->badgeColor('danger')
                        ->badgeTooltip('Visitas a direcciones que no existen')
                        ->schema([$this->widgetGrid([OutboundLinks::class, NotFoundPages::class])]),
                ]),
            Callout::make('Cómo se obtienen estas cifras')
                ->description('Un visitante es un navegador distinto en un mismo día (quien vuelve otro día se cuenta de nuevo); una visita es una serie de páginas sin pausas de más de '
                    .PageViewRecorder::SESSION_TIMEOUT_MINUTES.' minutos; el tiempo cuenta solo mientras la página está visible. No se cuentan robots ni personal del panel, y no se guarda la IP ni ningún dato personal.')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->color('gray'),
        ]);
    }

    /**
     * @param  list<class-string>  $widgets
     */
    private function widgetGrid(array $widgets): Grid
    {
        return Grid::make(['default' => 1, 'lg' => 2])
            ->schema(fn (): array => $this->getWidgetsSchemaComponents($widgets));
    }

    /**
     * Every widget of the page, so Filament knows them all (the layout itself is built in content()).
     */
    public function getWidgets(): array
    {
        return [
            LiveVisitors::class,
            SiteOverview::class,
            VisitorsTrend::class,
            TrafficSources::class,
            Devices::class,
            Hours::class,
            Technology::class,
            TopPages::class,
            Sections::class,
            SearchTerms::class,
            SearchesWithoutResults::class,
            DocumentViews::class,
            Downloads::class,
            OutboundLinks::class,
            NotFoundPages::class,
        ];
    }
}
