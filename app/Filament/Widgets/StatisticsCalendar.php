<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Support\Documents\TitlePeriod;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Month-by-month view of the statistics the user handles, so the statistics office sees at a glance which
 * months are published, which are uploaded but hidden, and which are still missing. The month comes from
 * the title, as on the public page.
 */
class StatisticsCalendar extends Widget
{
    protected string $view = 'filament.widgets.statistics-calendar';

    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public int $year;

    public function mount(): void
    {
        // In January the reports still being prepared are December's, so the calendar opens on last year.
        $this->year = now()->subMonthNoOverflow()->year;
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasPermission('documentos.view') && static::types()->isNotEmpty();
    }

    public function previousYear(): void
    {
        $this->year = max($this->year - 1, $this->firstYear());
    }

    public function nextYear(): void
    {
        $this->year = min($this->year + 1, now()->year);
    }

    /**
     * @return Collection<int, DocumentType>
     */
    protected static function types(): Collection
    {
        return DocumentResource::scopeAllowedTypes(DocumentType::query())->monthlyStatistics()->orderBy('sort_order')->orderBy('name')->get();
    }

    protected function firstYear(): int
    {
        return (int) (Document::query()->whereIn('document_type_id', static::types()->modelKeys())->min('period_year') ?? now()->year);
    }

    /**
     * @return array{
     *     year: int,
     *     canGoBack: bool,
     *     canGoForward: bool,
     *     months: list<string>,
     *     missing: int,
     *     rows: list<array{name: string, total: int, withoutMonth: int, uploadUrl: string|null, cells: list<array{state: string, count: int, label: string, url: string|null}>}>,
     * }
     */
    protected function getViewData(): array
    {
        $types = static::types();
        $today = today();
        $dueMonths = match (true) {
            $this->year < $today->year => 12,
            $this->year === $today->year => $today->month - 1,
            default => 0,
        };
        $canUpload = (bool) auth()->user()?->can('create', Document::class);

        // A December report uploaded under the next year still belongs to December, so neighbouring years are read too.
        $documents = Document::query()
            ->whereIn('document_type_id', $types->modelKeys())
            ->whereBetween('period_year', [$this->year - 1, $this->year + 1])
            ->get(['id', 'document_type_id', 'title', 'period_year', 'is_published'])
            ->groupBy('document_type_id');

        $rows = $types->map(function (DocumentType $type) use ($documents, $dueMonths, $canUpload): array {
            $months = array_fill(1, 12, ['published' => 0, 'drafts' => 0]);
            $withoutMonth = 0;

            foreach ($documents->get($type->id, []) as $document) {
                $period = TitlePeriod::fromTitle($document->title, $document->period_year);

                if ($period->hasMonth() && $period->year === $this->year) {
                    $months[$period->month][$document->is_published ? 'published' : 'drafts']++;
                } elseif (! $period->hasMonth() && $document->period_year === $this->year) {
                    $withoutMonth++;
                }
            }

            $uploadUrl = $canUpload
                ? DocumentResource::getUrl('index', ['action' => 'bulkUpload', 'actionArguments' => ['tipo' => $type->slug, 'anio' => $this->year]])
                : null;

            $cells = [];
            foreach ($months as $month => ['published' => $published, 'drafts' => $drafts]) {
                $monthName = Carbon::create($this->year, $month)->translatedFormat('F');
                $listUrl = DocumentResource::getUrl('index', [
                    'filters' => ['documentType' => ['value' => $type->id], 'period_year' => ['value' => $this->year]],
                    'search' => $monthName,
                ]);

                $cells[] = match (true) {
                    $published > 0 => ['state' => 'published', 'count' => $published + $drafts, 'url' => $listUrl,
                        'label' => Str::ucfirst($monthName).': '.trans_choice('{1} 1 documento publicado|[2,*] :count documentos publicados', $published).($drafts > 0 ? " y {$drafts} sin publicar" : '')],
                    $drafts > 0 => ['state' => 'draft', 'count' => $drafts, 'url' => $listUrl,
                        'label' => Str::ucfirst($monthName).': '.trans_choice('{1} 1 documento sin publicar|[2,*] :count documentos sin publicar', $drafts)],
                    $month <= $dueMonths => ['state' => 'missing', 'count' => 0, 'url' => $uploadUrl,
                        'label' => Str::ucfirst($monthName).': sin documentos'.($uploadUrl ? '. Subir ahora' : '')],
                    default => ['state' => 'upcoming', 'count' => 0, 'url' => null,
                        'label' => Str::ucfirst($monthName).': todavía no corresponde'],
                };
            }

            return [
                'name' => $type->name,
                'total' => array_sum(array_map(fn (array $month): int => $month['published'] + $month['drafts'], $months)) + $withoutMonth,
                'withoutMonth' => $withoutMonth,
                'uploadUrl' => $uploadUrl,
                'cells' => $cells,
            ];
        })->values()->all();

        return [
            'year' => $this->year,
            'canGoBack' => $this->year > $this->firstYear(),
            'canGoForward' => $this->year < now()->year,
            'months' => array_map(fn (int $month): string => rtrim(Carbon::create(2000, $month)->translatedFormat('M'), '.'), range(1, 12)),
            'missing' => collect($rows)->sum(fn (array $row): int => collect($row['cells'])->where('state', 'missing')->count()),
            'rows' => $rows,
        ];
    }
}
