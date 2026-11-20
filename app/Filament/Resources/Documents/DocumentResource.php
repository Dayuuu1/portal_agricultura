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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
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
                Section::make('Datos del documento')
                    ->description('Cómo se identificará el documento en el buscador y en su ficha pública.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->placeholder('Ej.: Resolución Directoral N° 045-2026 que aprueba el POI 2027')
                            ->helperText('Describa el contenido; evite usar solo el nombre del archivo.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));

                                    if ($year = Document::inferPeriodYear((string) $state)) {
                                        $set('period_year', $year);
                                    }
                                }
                            }),
                        TextInput::make('slug')
                            ->label('URL amigable')
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('code')
                            ->label('Número / código')
                            ->placeholder('RD N° 001-2026-GOREMAD/GRDE/DRDAR')
                            ->maxLength(255),
                        Select::make('document_type_id')
                            ->label('Tipo de documento')
                            ->relationship('documentType', 'name', fn (Builder $query): Builder => static::scopeAllowedTypes($query))
                            ->getOptionLabelFromRecordUsing(fn (DocumentType $record): string => "{$record->name} · {$record->section->getLabel()}")
                            ->default(fn (): ?int => self::typeFromQuery())
                            ->helperText(fn (): string => static::allowedTypeIds() === null
                                ? 'Define la sección del portal donde aparecerá.'
                                : 'Define la sección del portal donde aparecerá. Su rol solo permite algunos tipos de documento.')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('organizational_unit_id')
                            ->label('Órgano / unidad emisora')
                            ->relationship('organizationalUnit', 'name')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('issued_at')
                            ->label('Fecha de emisión')
                            ->helperText('Fecha del documento. Ordena el listado público.')
                            ->default(now())
                            ->maxDate(now()),
                        TextInput::make('period_year')
                            ->label('Año de referencia')
                            ->helperText('Año al que corresponde la información. Ej.: un boletín de diciembre 2025 publicado en enero 2026 es del 2025. Con este año se agrupa en el portal.')
                            ->numeric()
                            ->minValue(1990)
                            ->maxValue(now()->year + 1)
                            ->default(now()->year)
                            ->required(),
                        Textarea::make('description')
                            ->label('Descripción / sumilla')
                            ->helperText('Opcional. Un resumen breve ayuda a encontrarlo con el buscador.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Archivo')
                    ->description('Suba el archivo o indique un enlace externo.')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->columnSpan(1)
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
                            ->helperText('PDF, Word o Excel. Máximo 20 MB.'),
                        TextInput::make('external_url')
                            ->label('O enlace externo')
                            ->url()
                            ->helperText('Use solo si el archivo está alojado en otra plataforma (p. ej. gob.pe).'),
                        static::publishedToggle('Publicado', 'Si lo desmarca, el documento queda oculto para el público.'),
                    ]),
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
