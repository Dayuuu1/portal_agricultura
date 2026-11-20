<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentSection;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Widgets\StatisticsCalendar;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\OrganizationalUnit;
use App\Support\Documents\TitlePeriod;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    private const ACCEPTED_FILE_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function getSubheading(): string
    {
        $allowed = DocumentResource::allowedTypeIds();

        if ($allowed !== null) {
            $sections = DocumentResource::allowedSections();
            $coversWholeSection = count($sections) === 1
                && DocumentType::query()->where('section', $sections[0])->whereKeyNot($allowed)->doesntExist();
            $scope = $coversWholeSection
                ? 'los documentos de «'.$sections[0]->getLabel().'»'
                : DocumentType::query()->whereKey($allowed)->orderBy('sort_order')->pluck('name')->implode(', ');

            return "Su rol le permite subir {$scope}. Para varios archivos a la vez use «Subir varios archivos»: elige el tipo una vez y revisa los títulos antes de guardar.";
        }

        return 'Repositorio de documentos públicos: resoluciones, documentos de gestión, boletines y estadística. Para varios archivos a la vez (por ejemplo, la estadística del mes) use «Subir varios archivos».';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->bulkUploadAction(),
            CreateAction::make()
                ->label('Subir documento')
                ->color(fn (): string => DocumentResource::managesOnlyAgrarianInformation() ? 'gray' : 'primary'),
        ];
    }

    /**
     * The publications calendar shows what the statistics office still has to upload. It opens with the
     * «Información agraria» tab, or always for staff who only handle that section.
     */
    protected function getHeaderWidgets(): array
    {
        return $this->activeTab === 'informacion-agraria' || DocumentResource::managesOnlyAgrarianInformation()
            ? [StatisticsCalendar::class]
            : [];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * One document per file. Step one sets what all files share; step two lists the files with the title and
     * year each one will get, read from its file name, so they can be corrected before anything is saved.
     */
    private function bulkUploadAction(): Action
    {
        return Action::make('bulkUpload')
            ->label('Subir varios archivos')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color(fn (): string => DocumentResource::managesOnlyAgrarianInformation() ? 'primary' : 'gray')
            ->modalHeading('Subir varios documentos a la vez')
            ->modalDescription('Se crea un documento por cada archivo. Ideal para la estadística del mes.')
            ->modalSubmitActionLabel('Subir documentos')
            ->modalWidth(Width::FiveExtraLarge)
            ->authorize('create')
            ->mountUsing(function (Schema $schema, array $arguments): void {
                $schema->fill();

                // Links from the publications calendar choose the type and year: ?action=bulkUpload&actionArguments[tipo]=…&actionArguments[anio]=…
                $type = is_string($arguments['tipo'] ?? null)
                    ? DocumentResource::scopeAllowedTypes(DocumentType::query())->where('slug', $arguments['tipo'])->value('id')
                    : null;
                $year = filter_var($arguments['anio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1990, 'max_range' => now()->year + 1]]);

                if ($type || $year) {
                    $schema->fill([...$schema->getRawState(), ...array_filter(['document_type_id' => $type, 'organizational_unit_id' => DocumentResource::usualUnitFor($type), 'period_year' => $year ?: null])], shouldCallHydrationHooks: false);
                }
            })
            ->steps([
                Step::make('Tipo y año')
                    ->description('Datos comunes a todos los archivos')
                    ->icon(Heroicon::OutlinedTag)
                    ->columns(2)
                    ->schema([
                        Select::make('document_type_id')
                            ->label('Tipo de documento')
                            ->options(fn (): array => DocumentResource::groupedTypeOptions())
                            ->default(fn (): ?int => DocumentResource::defaultTypeId())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                                if (filled($state) && blank($get('organizational_unit_id'))) {
                                    $set('organizational_unit_id', DocumentResource::usualUnitFor($state));
                                }
                            })
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('period_year')
                            ->label('Año de referencia')
                            ->helperText('Se usa en los archivos cuyo nombre no indica el año.')
                            ->numeric()
                            ->minValue(1990)
                            ->maxValue(now()->year + 1)
                            ->default(now()->year)
                            ->required(),
                        DatePicker::make('issued_at')
                            ->label('Fecha de publicación')
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                        Select::make('organizational_unit_id')
                            ->label('Órgano / unidad emisora')
                            ->options(fn (): array => OrganizationalUnit::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                            ->default(fn (): ?int => DocumentResource::usualUnitFor(DocumentResource::defaultTypeId()))
                            ->helperText('Se propone la unidad que suele emitir este tipo de documento.')
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
                Step::make('Archivos')
                    ->description('Suba y revise los títulos')
                    ->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->schema([
                        FileUpload::make('files')
                            ->label('Archivos')
                            ->helperText('Hasta 30 archivos PDF, Word o Excel de 20 MB como máximo. El título se toma del nombre del archivo: «Producción pecuaria octubre 2026.pdf» se publica como «Producción pecuaria octubre 2026».')
                            ->multiple()
                            ->disk('public')
                            ->directory(fn (): string => 'documentos/'.now()->year)
                            ->acceptedFileTypes(self::ACCEPTED_FILE_TYPES)
                            ->maxSize(20480)
                            ->maxFiles(30)
                            ->storeFileNamesIn('file_names')
                            ->live()
                            ->afterStateUpdated(fn (FileUpload $component, Get $get, Set $set) => $set('documents', $this->reviewRows($component, $get)))
                            ->required(),
                        Hidden::make('file_names'),
                        Repeater::make('documents')
                            ->label('Revise cómo se publicará cada archivo')
                            ->helperText('Corrija el título o el año si hace falta. En la estadística mensual el portal agrupa por el mes que indica el título.')
                            ->table([
                                TableColumn::make('Título')->markAsRequired(),
                                TableColumn::make('Año')->markAsRequired()->width('8rem'),
                                TableColumn::make('Revisión')->width('12rem'),
                            ])
                            ->schema([
                                Hidden::make('file_name'),
                                TextInput::make('title')
                                    ->label('Título')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->belowContent(fn (Get $get): string => 'Archivo: '.$get('file_name')),
                                TextInput::make('period_year')
                                    ->label('Año')
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(now()->year + 1)
                                    ->required()
                                    ->live(onBlur: true),
                                Text::make(fn (Get $get): string => DocumentResource::checkTitle($get('title'), $get('../../document_type_id'), $get('period_year'))->badge())
                                    ->badge()
                                    ->color(fn (Get $get): string => DocumentResource::checkTitle($get('title'), $get('../../document_type_id'), $get('period_year'))->color()),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->visible(fn (Get $get): bool => filled($get('documents'))),
                        Toggle::make('is_published')
                            ->label('Publicar en el portal de inmediato')
                            ->default(fn (): bool => DocumentResource::canPublish())
                            ->disabled(fn (): bool => ! DocumentResource::canPublish())
                            ->helperText(fn (): ?string => DocumentResource::canPublish()
                                ? null
                                : 'Su rol no permite publicar: los documentos quedarán ocultos hasta que una persona autorizada los publique.'),
                    ]),
            ])
            ->action(function (array $data): void {
                $data = DocumentResource::enforcePublishPermission($data, creating: true);
                $reviewed = collect($data['documents'] ?? [])->values();
                $created = 0;

                foreach ($data['files'] as $path) {
                    $originalName = $data['file_names'][$path] ?? basename($path);

                    // The review table is matched by file name; without it (e.g. scripted uploads) the file name decides.
                    $rowIndex = $reviewed->search(fn (array $row): bool => ($row['file_name'] ?? null) === $originalName);
                    $row = $rowIndex === false ? $this->reviewRow($originalName, (int) $data['period_year']) : $reviewed->pull($rowIndex);

                    Document::query()->create([
                        'document_type_id' => $data['document_type_id'],
                        'organizational_unit_id' => $data['organizational_unit_id'] ?? null,
                        'title' => $row['title'],
                        'period_year' => $row['period_year'],
                        'issued_at' => $data['issued_at'],
                        'file_path' => $path,
                        'file_name' => $originalName,
                        'is_published' => (bool) ($data['is_published'] ?? false),
                    ]);

                    $created++;
                }

                Notification::make()
                    ->title($created === 1 ? 'Se subió 1 documento' : "Se subieron {$created} documentos")
                    ->body(($data['is_published'] ?? false) ? 'Ya se pueden consultar en el portal.' : 'Quedaron sin publicar, en la pestaña «Sin publicar».')
                    ->success()
                    ->send();
            });
    }

    /**
     * One review row per uploaded file, keyed like the upload so edits survive adding or removing files.
     *
     * @return array<string, array{file_name: string, title: string, period_year: int}>
     */
    private function reviewRows(FileUpload $component, Get $get): array
    {
        $previous = $get('documents') ?? [];
        $fallbackYear = (int) ($get('period_year') ?: now()->year);
        $rows = [];

        foreach ($component->getRawState() ?? [] as $key => $file) {
            $fileName = $file instanceof TemporaryUploadedFile ? $file->getClientOriginalName() : basename((string) $file);
            $rows[$key] = $previous[$key] ?? $this->reviewRow($fileName, $fallbackYear);
        }

        return $rows;
    }

    /**
     * @return array{file_name: string, title: string, period_year: int}
     */
    private function reviewRow(string $fileName, int $fallbackYear): array
    {
        $title = Document::titleFromFileName($fileName);

        return [
            'file_name' => $fileName,
            'title' => $title,
            'period_year' => TitlePeriod::fromTitle($title, $fallbackYear)->year ?? Document::inferPeriodYear($title) ?? $fallbackYear,
        ];
    }

    /**
     * Section tabs; counts only include the document types the user may handle. Staff limited to a single
     * section only get «Todos», which already is that section.
     */
    public function getTabs(): array
    {
        $section = fn (DocumentSection $section) => fn (Builder $query) => $query->whereRelation('documentType', 'section', $section->value);
        $count = fn (?DocumentSection $section = null): int => DocumentResource::getEloquentQuery()
            ->when($section, fn (Builder $query) => $query->whereRelation('documentType', 'section', $section->value))
            ->count();
        $allowedSections = DocumentResource::allowedSections();
        $sectionTab = fn (string $label, DocumentSection $documentSection): Tab => Tab::make($label)
            ->modifyQueryUsing($section($documentSection))
            ->badge($count($documentSection))
            ->visible(count($allowedSections) > 1 && in_array($documentSection, $allowedSections, true));

        return [
            'todos' => Tab::make(count($allowedSections) === 1 && DocumentResource::allowedTypeIds() !== null ? $allowedSections[0]->getLabel() : 'Todos')
                ->badge($count()),
            'normas' => $sectionTab('Normas y resoluciones', DocumentSection::Normativa),
            'gestion' => $sectionTab('Gestión institucional', DocumentSection::Gestion),
            'informacion-agraria' => $sectionTab('Información agraria', DocumentSection::InformacionAgraria),
            'sin-publicar' => Tab::make('Sin publicar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', false))
                ->badge(DocumentResource::getEloquentQuery()->where('is_published', false)->count())
                ->badgeColor('warning'),
        ];
    }
}
