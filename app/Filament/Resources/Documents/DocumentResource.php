<?php

namespace App\Filament\Resources\Documents;

use App\Enums\DocumentSection;
use App\Filament\Concerns\ControlsPublishing;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Support\Documents\TitleCheck;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group as LayoutGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class DocumentResource extends Resource
{
    use ControlsPublishing, HasSpanishLabels;

    protected static ?string $model = Document::class;

    protected static ?string $slug = 'documentos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Documentos';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                LayoutGroup::make([
                    Section::make('Archivo')
                        ->description('PDF, Word o Excel de hasta 20 MB. Si lo sube primero, el título y el año se proponen a partir del nombre del archivo.')
                        ->icon(Heroicon::OutlinedPaperClip)
                        ->schema([
                            FileUpload::make('file_path')
                                ->label('Archivo')
                                ->disk('public')
                                ->directory(fn (): string => 'documentos/'.now()->year)
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->maxSize(20480)
                                ->storeFileNamesIn('file_name')
                                ->downloadable()
                                ->openable()
                                // A published entry without a file is a dead end for the public.
                                ->required(fn (Get $get): bool => (bool) $get('is_published') && blank($get('external_url')))
                                ->validationMessages(['required' => 'Suba el archivo o indique un enlace externo para poder publicarlo.'])
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, mixed $state, string $operation): void {
                                    if ($operation !== 'create' || ! $state instanceof TemporaryUploadedFile || filled($get('title'))) {
                                        return;
                                    }

                                    $title = Document::titleFromFileName($state->getClientOriginalName());
                                    $set('title', $title);
                                    $set('slug', Str::slug($title));

                                    if ($year = Document::inferPeriodYear($title)) {
                                        $set('period_year', $year);
                                    }
                                }),
                            TextInput::make('external_url')
                                ->label('¿El archivo está en otra web? Pegue aquí el enlace')
                                ->placeholder('https://www.gob.pe/…')
                                ->url()
                                ->live(onBlur: true)
                                ->visible(fn (Get $get): bool => blank($get('file_path'))),
                        ]),
                    Section::make('Datos del documento')
                        ->description('Cómo se identificará en el buscador y en su ficha pública.')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->columns(2)
                        ->schema([
                            Select::make('document_type_id')
                                ->label('Tipo de documento')
                                ->options(fn (): array => static::groupedTypeOptions())
                                ->default(fn (): ?int => static::defaultTypeId())
                                ->helperText(fn (): string => static::allowedTypeIds() === null
                                    ? 'Define la sección del portal donde aparecerá.'
                                    : 'Define la sección del portal donde aparecerá. Su rol solo permite algunos tipos de documento.')
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                                    if (filled($state) && blank($get('organizational_unit_id'))) {
                                        $set('organizational_unit_id', static::usualUnitFor($state));
                                    }
                                })
                                ->required(),
                            TextInput::make('period_year')
                                ->label('Año de referencia')
                                ->helperText('Año al que corresponde la información; con él se agrupa en el portal. Un boletín de diciembre 2025 publicado en enero es del 2025.')
                                ->numeric()
                                ->minValue(1990)
                                ->maxValue(now()->year + 1)
                                ->default(now()->year)
                                ->live(onBlur: true)
                                ->required(),
                            TextInput::make('title')
                                ->label('Título')
                                ->placeholder(fn (Get $get): string => static::typeById($get('document_type_id'))?->isMonthlyStatistics()
                                    ? 'Ej.: Producción de los principales cultivos – octubre 2026'
                                    : 'Ej.: Resolución Directoral N° 045-2026 que aprueba el POI 2027')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull()
                                // Replaces helperText(), which Filament renders through the same slot.
                                ->belowContent(function (Get $get, ?Model $record): array {
                                    $check = static::checkTitle($get('title'), $get('document_type_id'), $get('period_year'), $record?->getKey());

                                    // Only what needs fixing; the month it will be filed under is shown in the publication summary.
                                    return array_filter([
                                        Text::make('Describa el contenido; evite usar solo el nombre del archivo.'),
                                        $check->needsAttention() ? Text::make((string) $check->message())->color('warning') : null,
                                    ]);
                                })
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug((string) $state));

                                        if ($year = Document::inferPeriodYear((string) $state)) {
                                            $set('period_year', $year);
                                        }
                                    }
                                }),
                            // Only resolutions and management documents carry a number; statistics never do.
                            TextInput::make('code')
                                ->label('Número / código')
                                ->placeholder('RD N° 001-2026-GOREMAD/GRDE/DRDAR')
                                ->maxLength(255)
                                ->visible(fn (Get $get): bool => ! static::typeById($get('document_type_id'))?->isMonthlyStatistics()),
                            Select::make('organizational_unit_id')
                                ->label('Órgano / unidad emisora')
                                ->relationship('organizationalUnit', 'name')
                                ->default(fn (): ?int => static::usualUnitFor(static::defaultTypeId()))
                                ->helperText('Se propone la unidad que suele emitir este tipo de documento.')
                                ->searchable()
                                ->preload(),
                            DatePicker::make('issued_at')
                                ->label('Fecha de emisión')
                                ->helperText('Fecha del documento. Ordena el listado público.')
                                ->default(now())
                                ->maxDate(now()),
                            Textarea::make('description')
                                ->label('Descripción / sumilla')
                                ->helperText('Opcional. Un resumen breve ayuda a encontrarlo con el buscador.')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(2),
                LayoutGroup::make([
                    Section::make('Publicación')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->schema([
                            static::publishedToggle('Publicado en el portal', 'Si lo desmarca, el documento queda guardado pero oculto para el público.')->live(),
                            View::make('filament.documents.publication-summary')
                                ->viewData(fn (Get $get, ?Model $record): array => static::publicationSummary($get, $record)),
                        ]),
                    Section::make('Opciones avanzadas')
                        ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('slug')
                                ->label('URL amigable')
                                ->helperText('Se genera sola a partir del título. Cámbiela solo si necesita una dirección específica.')
                                ->maxLength(255)
                                ->alphaDash()
                                ->unique(ignoreRecord: true)
                                ->live(onBlur: true),
                        ]),
                ])->columnSpan(1)->extraAttributes(['class' => 'lg:sticky lg:top-20']),
            ]);
    }

    /**
     * Pre-selects the document type when arriving from "Subir documento" on a type (?tipo=slug).
     */
    public static function typeFromQuery(): ?int
    {
        $slug = request()->query('tipo');

        return is_string($slug) && $slug !== ''
            ? static::scopeAllowedTypes(DocumentType::query())->where('slug', $slug)->value('id')
            : null;
    }

    /**
     * Users only see and handle the document types their roles allow.
     *
     * @return Builder<Document>
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return $user instanceof User
            ? parent::getEloquentQuery()->visibleTo($user)
            : parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    /**
     * @return list<int>|null Null when every type is allowed.
     */
    public static function allowedTypeIds(): ?array
    {
        $user = auth()->user();

        return $user instanceof User ? $user->allowedDocumentTypeIds() : [];
    }

    /**
     * @param  Builder<DocumentType>  $query
     * @return Builder<DocumentType>
     */
    public static function scopeAllowedTypes(Builder $query): Builder
    {
        $allowed = static::allowedTypeIds();

        return $allowed === null ? $query : $query->whereKey($allowed);
    }

    /**
     * @return array<int, string>
     */
    public static function allowedTypeOptions(): array
    {
        return static::scopeAllowedTypes(DocumentType::query())->orderBy('sort_order')->pluck('name', 'id')->all();
    }

    /**
     * Allowed types under the portal section they are published in, for the type selects.
     *
     * @return array<string, array<int, string>>
     */
    public static function groupedTypeOptions(): array
    {
        $sectionOrder = array_flip(array_column(DocumentSection::cases(), 'value'));

        return static::scopeAllowedTypes(DocumentType::query())->get()
            ->sortBy(fn (DocumentType $type): string => sprintf('%d-%05d-%s', $sectionOrder[$type->section->value], $type->sort_order, $type->name))
            ->groupBy(fn (DocumentType $type): string => $type->section->getLabel())
            ->map(fn (Collection $types): array => $types->pluck('name', 'id')->all())
            ->all();
    }

    /**
     * Type to start the upload forms with: the one in the link, or the only one the user handles.
     */
    public static function defaultTypeId(): ?int
    {
        $allowed = static::allowedTypeIds();

        return static::typeFromQuery() ?? ($allowed !== null && count($allowed) === 1 ? $allowed[0] : null);
    }

    /**
     * @return list<DocumentSection>
     */
    public static function allowedSections(): array
    {
        return static::scopeAllowedTypes(DocumentType::query())->distinct()->pluck('section')
            ->map(fn (DocumentSection|string $section): DocumentSection => $section instanceof DocumentSection ? $section : DocumentSection::from($section))
            ->sortBy(fn (DocumentSection $section): int => array_search($section, DocumentSection::cases(), true))
            ->values()
            ->all();
    }

    /**
     * Staff of the statistics office: every type they handle is published in «Información agraria».
     */
    public static function managesOnlyAgrarianInformation(): bool
    {
        return static::allowedTypeIds() !== null && static::allowedSections() === [DocumentSection::InformacionAgraria];
    }

    /**
     * Allowed type by id, memoized for the many form closures that depend on it.
     */
    public static function typeById(mixed $typeId): ?DocumentType
    {
        return filled($typeId) && is_numeric($typeId)
            ? once(fn (): ?DocumentType => static::scopeAllowedTypes(DocumentType::query())->find((int) $typeId))
            : null;
    }

    /**
     * Unit that usually issues this type (each type has been issued by a single office so far), proposed in the forms.
     */
    public static function usualUnitFor(mixed $typeId): ?int
    {
        if (blank($typeId) || ! is_numeric($typeId)) {
            return null;
        }

        $unitId = Document::query()
            ->where('document_type_id', (int) $typeId)
            ->whereNotNull('organizational_unit_id')
            ->groupBy('organizational_unit_id')
            ->orderByRaw('count(*) desc')
            ->value('organizational_unit_id');

        return $unitId === null ? null : (int) $unitId;
    }

    /**
     * Where the document will show on the public site, for the summary next to the form.
     *
     * @return array{section: ?string, type: ?string, year: ?int, expectsMonth: bool, period: ?string, isPublished: bool, url: ?string}
     */
    public static function publicationSummary(Get $get, ?Model $record = null): array
    {
        $type = static::typeById($get('document_type_id'));
        $year = is_numeric($get('period_year')) ? (int) $get('period_year') : null;
        $check = static::checkTitle($get('title'), $get('document_type_id'), $get('period_year'), $record?->getKey());
        $slug = $get('slug');

        return [
            'section' => $type?->section->getLabel(),
            'type' => $type?->name,
            'year' => $type?->group_by_year ? $year : null,
            'expectsMonth' => $check->expectsMonth,
            'period' => $check->period,
            'isPublished' => (bool) $get('is_published'),
            'url' => is_string($slug) && $slug !== '' ? route('documents.show', $slug) : null,
        ];
    }

    /**
     * Duplicate and month check of a title; memoized because the bulk review table asks once per row and per render.
     */
    public static function checkTitle(mixed $title, mixed $typeId, mixed $year, mixed $ignoreDocumentId = null): TitleCheck
    {
        return once(fn (): TitleCheck => TitleCheck::for(
            is_string($title) ? $title : null,
            filled($typeId) ? static::scopeAllowedTypes(DocumentType::query())->find($typeId) : null,
            is_numeric($year) ? (int) $year : null,
            is_numeric($ignoreDocumentId) ? (int) $ignoreDocumentId : null,
        ));
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Tipo' => $record->documentType?->name ?? '—',
            'Emisión' => $record->issued_at?->format('d/m/Y') ?? '—',
        ];
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'code'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['documentType', 'organizationalUnit']))
            ->striped()
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->emptyStateHeading('No hay documentos en esta vista')
            ->emptyStateDescription('Use «Subir documento» para agregar uno, o pruebe con otra pestaña o filtro.')
            ->columns([
                TextColumn::make('title')
                    ->label('Documento')
                    ->width('40%')
                    ->searchable(['title', 'code'])
                    ->weight('semibold')
                    ->description(fn (Document $record): string => collect([$record->code, $record->organizationalUnit?->acronym ?? $record->organizationalUnit?->name])->filter()->implode(' · '))
                    ->limit(90)
                    ->tooltip(fn (Document $record): string => $record->title)
                    ->wrap(),
                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (Document $record): string => match ($record->documentType?->section) {
                        DocumentSection::Normativa => 'info',
                        DocumentSection::InformacionAgraria => 'warning',
                        default => 'primary',
                    })
                    ->toggleable(),
                TextColumn::make('period_year')
                    ->label('Año')
                    ->description(fn (Document $record): ?string => $record->issued_at?->format('d/m/Y'))
                    ->sortable(['period_year', 'issued_at']),
                TextColumn::make('file')
                    ->label('Archivo')
                    ->state(fn (Document $record): string => match (true) {
                        filled($record->file_path) => trim(($record->file_extension ?? 'Archivo').' · '.($record->human_file_size ?? ''), ' ·'),
                        filled($record->external_url) => 'Enlace externo',
                        default => 'Sin archivo',
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Sin archivo' ? 'danger' : 'gray')
                    ->icon(fn (string $state): Heroicon => $state === 'Sin archivo' ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedPaperClip),
                TextColumn::make('downloads')
                    ->label('Descargas')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Público')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('documentType')
                    ->label('Tipo')
                    ->relationship('documentType', 'name', fn (Builder $query): Builder => static::scopeAllowedTypes($query))
                    ->preload(),
                SelectFilter::make('period_year')
                    ->label('Año de referencia')
                    ->options(fn (): array => static::getEloquentQuery()->whereNotNull('period_year')->distinct()->orderByDesc('period_year')
                        ->pluck('period_year', 'period_year')->all()),
                TernaryFilter::make('is_published')->label('Publicado'),
            ])
            ->groups([
                Group::make('period_year')
                    ->label('Año')
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query->orderBy('period_year', $direction === 'asc' ? 'desc' : 'asc'))
                    ->collapsible(),
                Group::make('documentType.name')
                    ->label('Tipo de documento')
                    ->collapsible(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('download')
                        ->label('Descargar archivo')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn (Document $record): ?string => $record->file_path ? Storage::disk('public')->url($record->file_path) : $record->external_url)
                        ->openUrlInNewTab()
                        ->visible(fn (Document $record): bool => $record->hasFile()),
                    Action::make('viewOnSite')
                        ->label('Ver en el portal')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (Document $record): string => route('documents.show', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (Document $record): bool => $record->is_published),
                    DeleteAction::make(),
                ])->tooltip('Más acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('reclassify')
                        ->label('Cambiar tipo o año')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->modalHeading('Cambiar tipo o año de los documentos seleccionados')
                        ->modalDescription('Deje vacío lo que no quiera cambiar.')
                        ->authorizeIndividualRecords('update')
                        ->schema([
                            Select::make('document_type_id')
                                ->label('Nuevo tipo de documento')
                                ->options(fn (): array => static::allowedTypeOptions())
                                ->searchable(),
                            TextInput::make('period_year')
                                ->label('Nuevo año de referencia')
                                ->numeric()
                                ->minValue(1990)
                                ->maxValue(now()->year + 1),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $changes = array_filter($data, fn ($value): bool => filled($value));
                            $records->each(fn (Document $record) => $record->update($changes));
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Documentos actualizados'),
                    BulkAction::make('publish')
                        ->label('Publicar')
                        ->icon(Heroicon::OutlinedEye)
                        ->visible(fn (): bool => static::canPublish())
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()->authorizeIndividualRecords(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}
