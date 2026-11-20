<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentSection;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\OrganizationalUnit;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    public function getSubheading(): string
    {
        return 'Repositorio de documentos públicos: resoluciones, documentos de gestión, boletines y estadística. Para la estadística mensual use «Subir varios archivos»: elige el tipo y el año una sola vez para todos los PDF.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkUpload')
                ->label('Subir varios archivos')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->modalHeading('Subir varios documentos a la vez')
                ->modalDescription('Ideal para la estadística mensual: se crea un documento por cada archivo, usando el nombre del archivo como título. Después puede corregir cualquier título desde el listado.')
                ->modalSubmitActionLabel('Subir documentos')
                ->modalWidth(Width::ThreeExtraLarge)
                ->authorize('create')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('document_type_id')
                            ->label('Tipo de documento')
                            ->options(fn (): array => DocumentResource::allowedTypeOptions())
                            ->default(fn (): ?int => DocumentResource::typeFromQuery())
                            ->searchable()
                            ->required(),
                        TextInput::make('period_year')
                            ->label('Año de referencia')
                            ->helperText('Año al que corresponde la información.')
                            ->numeric()
                            ->minValue(1990)
                            ->maxValue(now()->year + 1)
                            ->default(now()->year)
                            ->required(),
                        DatePicker::make('issued_at')
                            ->label('Fecha de emisión')
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                    ]),
                    Select::make('organizational_unit_id')
                        ->label('Órgano / unidad emisora')
                        ->options(fn (): array => OrganizationalUnit::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    FileUpload::make('files')
                        ->label('Archivos')
                        ->helperText('Hasta 30 archivos PDF, Word o Excel de 20 MB como máximo. Nombre los archivos de forma clara, por ejemplo «Producción pecuaria octubre 2026.pdf».')
                        ->multiple()
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
                        ->maxFiles(30)
                        ->storeFileNamesIn('file_names')
                        ->required(),
                    Hidden::make('file_names'),
                    Toggle::make('is_published')
                        ->label('Publicar en el portal de inmediato')
                        ->default(fn (): bool => DocumentResource::canPublish())
                        ->disabled(fn (): bool => ! DocumentResource::canPublish())
                        ->helperText(fn (): ?string => DocumentResource::canPublish()
                            ? null
                            : 'Su rol no permite publicar: los documentos quedarán ocultos hasta que una persona autorizada los publique.'),
                ])
                ->action(function (array $data): void {
                    $data = DocumentResource::enforcePublishPermission($data, creating: true);
                    $created = 0;

                    foreach ($data['files'] as $path) {
                        $originalName = $data['file_names'][$path] ?? basename($path);

                        Document::query()->create([
                            'document_type_id' => $data['document_type_id'],
                            'organizational_unit_id' => $data['organizational_unit_id'] ?? null,
                            'title' => Document::titleFromFileName($originalName),
                            'period_year' => $data['period_year'],
                            'issued_at' => $data['issued_at'],
                            'file_path' => $path,
                            'file_name' => $originalName,
                            'is_published' => (bool) ($data['is_published'] ?? false),
                        ]);

                        $created++;
                    }

                    Notification::make()
                        ->title($created === 1 ? 'Se subió 1 documento' : "Se subieron {$created} documentos")
                        ->body('Revise los títulos en el listado y corríjalos si es necesario.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Subir documento'),
        ];
    }

    /**
     * Section tabs; counts only include the document types the user may handle,
     * and sections the user has no types in are hidden.
     */
    public function getTabs(): array
    {
        $section = fn (DocumentSection $section) => fn (Builder $query) => $query->whereRelation('documentType', 'section', $section->value);
        $count = fn (?DocumentSection $section = null): int => DocumentResource::getEloquentQuery()
            ->when($section, fn (Builder $query) => $query->whereRelation('documentType', 'section', $section->value))
            ->count();
        $allowedSections = DocumentResource::scopeAllowedTypes(DocumentType::query())->distinct()->pluck('section')
            ->map(fn (DocumentSection|string $value): string => $value instanceof DocumentSection ? $value->value : $value)
            ->all();
        $sectionTab = fn (string $label, DocumentSection $documentSection): Tab => Tab::make($label)
            ->modifyQueryUsing($section($documentSection))
            ->badge($count($documentSection))
            ->visible(in_array($documentSection->value, $allowedSections, true));

        return [
            'todos' => Tab::make('Todos')->badge($count()),
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
