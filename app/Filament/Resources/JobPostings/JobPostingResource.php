<?php

namespace App\Filament\Resources\JobPostings;

use App\Enums\JobPostingStatus;
use App\Filament\Concerns\ControlsPublishing;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\JobPostings\Pages\CreateJobPosting;
use App\Filament\Resources\JobPostings\Pages\EditJobPosting;
use App\Filament\Resources\JobPostings\Pages\ListJobPostings;
use App\Models\JobPosting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class JobPostingResource extends Resource
{
    use ControlsPublishing, HasSpanishLabels;

    protected static ?string $model = JobPosting::class;

    protected static ?string $slug = 'convocatorias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Convocatorias';

    protected static ?string $modelLabel = 'convocatoria';

    protected static ?string $pluralModelLabel = 'convocatorias';

    protected static ?string $recordTitleAttribute = 'code';

    private const MODALITIES = ['CAS', 'CAS por suplencia', 'CAS por reemplazo', 'Prácticas preprofesionales', 'Prácticas profesionales'];

    public static function getNavigationBadge(): ?string
    {
        $open = JobPosting::query()->where('status', JobPostingStatus::EnCurso)->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Convocatoria')
                    ->description('Datos generales del proceso tal como aparecen en las bases.')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->columns(3)
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')
                            ->placeholder('CAS N° 001-2026-GOREMAD-GRDE/DRDAR')
                            ->helperText('Escríbalo igual que en las bases; es como lo buscarán los postulantes.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', JobPosting::generateUniqueSlug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('URL amigable')
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        TextInput::make('title')
                            ->label('Puesto / objeto de la convocatoria')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Select::make('modality')
                            ->label('Modalidad')
                            ->options(array_combine(self::MODALITIES, self::MODALITIES))
                            ->default('CAS')
                            ->required(),
                        Select::make('status')
                            ->label('Estado')
                            ->options(JobPostingStatus::class)
                            ->default(JobPostingStatus::EnCurso)
                            ->helperText('Cámbielo a «Finalizada» al publicar el resultado final.')
                            ->required(),
                        DatePicker::make('starts_at')
                            ->label('Fecha de inicio')
                            ->default(now()),
                        DatePicker::make('ends_at')
                            ->label('Fecha de cierre')
                            ->helperText('Último día de postulación.')
                            ->afterOrEqual('starts_at'),
                        TextInput::make('positions_count')
                            ->label('N° de puestos')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Select::make('organizational_unit_id')
                            ->label('Unidad responsable')
                            ->relationship('organizationalUnit', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        RichEditor::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        static::publishedToggle('Publicada', 'Desmárquela para preparar la convocatoria sin mostrarla todavía.'),
                    ]),
                Section::make('Documentos del proceso')
                    ->description('Bases, comunicados y resultados de cada etapa, en orden cronológico. En el portal se muestran como una línea de tiempo.')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->schema([
                        Repeater::make('files')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Agregar documento')
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('label')
                                    ->label('Etapa / documento')
                                    ->datalist(['Bases del proceso', 'Comunicado', 'Resultado de evaluación curricular', 'Resultado de entrevista personal', 'Resultado final'])
                                    ->required()
                                    ->maxLength(255),
                                FileUpload::make('file_path')
                                    ->label('Archivo PDF')
                                    ->disk('public')
                                    ->directory('convocatorias')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(20480)
                                    ->openable()
                                    ->required(),
                                DateTimePicker::make('published_at')
                                    ->label('Fecha de publicación')
                                    ->default(now()),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->striped()
            ->emptyStateIcon(Heroicon::OutlinedBriefcase)
            ->emptyStateHeading('No hay convocatorias en esta vista')
            ->emptyStateDescription('Use «Nueva convocatoria» para registrar un proceso de selección.')
            ->columns([
                TextColumn::make('code')
                    ->label('Convocatoria')
                    ->searchable(['code', 'title'])
                    ->weight('semibold')
                    ->description(fn (JobPosting $record): string => Str::limit($record->title, 90).' · '.$record->modality)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('ends_at')
                    ->label('Plazo')
                    ->state(fn (JobPosting $record): string => collect([$record->starts_at?->format('d/m/Y'), $record->ends_at?->format('d/m/Y')])->filter()->implode(' → ') ?: 'Sin fechas')
                    ->description(fn (JobPosting $record): ?string => self::isOverdue($record) ? 'Plazo vencido: actualice el estado' : null)
                    ->color(fn (JobPosting $record): ?string => self::isOverdue($record) ? 'danger' : null)
                    ->icon(fn (JobPosting $record): ?Heroicon => self::isOverdue($record) ? Heroicon::OutlinedExclamationTriangle : null)
                    ->sortable(['ends_at']),
                TextColumn::make('files_count')
                    ->label('Documentos')
                    ->counts('files')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'gray')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(JobPostingStatus::class),
                SelectFilter::make('modality')
                    ->label('Modalidad')
                    ->options(fn (): array => JobPosting::query()->distinct()->orderBy('modality')->pluck('modality', 'modality')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('changeStatus')
                        ->label('Cambiar estado')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->modalHeading(fn (JobPosting $record): string => 'Cambiar estado de '.$record->code)
                        ->modalSubmitActionLabel('Guardar estado')
                        ->fillForm(fn (JobPosting $record): array => ['status' => $record->status])
                        ->schema([
                            Select::make('status')
                                ->label('Nuevo estado')
                                ->options(JobPostingStatus::class)
                                ->required(),
                        ])
                        ->authorize('update')
                        ->action(fn (JobPosting $record, array $data) => $record->update(['status' => $data['status']]))
                        ->successNotificationTitle('Estado actualizado'),
                    Action::make('viewOnSite')
                        ->label('Ver en el portal')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (JobPosting $record): string => route('job-postings.show', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (JobPosting $record): bool => $record->is_published),
                    DeleteAction::make(),
                ])->tooltip('Más acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords(),
                ]),
            ]);
    }

    private static function isOverdue(JobPosting $record): bool
    {
        return $record->status === JobPostingStatus::EnCurso && (bool) $record->ends_at?->isBefore(today());
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Estado' => $record->status?->getLabel() ?? '—',
            'Puesto' => Str::limit($record->title, 60),
        ];
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'title'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobPostings::route('/'),
            'create' => CreateJobPosting::route('/create'),
            'edit' => EditJobPosting::route('/{record}/edit'),
        ];
    }
}
