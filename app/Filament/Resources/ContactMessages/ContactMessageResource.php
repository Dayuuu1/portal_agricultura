<?php

namespace App\Filament\Resources\ContactMessages;

use App\Enums\ContactMessageType;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class ContactMessageResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = ContactMessage::class;

    protected static ?string $slug = 'mensajes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Atención al ciudadano';

    protected static ?string $navigationLabel = 'Mensajes recibidos';

    protected static ?string $modelLabel = 'mensaje';

    protected static ?string $pluralModelLabel = 'mensajes';

    protected static ?string $recordTitleAttribute = 'subject';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = ContactMessage::query()->unread()->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('type')->label('Tipo')->badge(),
                TextEntry::make('created_at')->label('Recibido')->dateTime('d/m/Y H:i'),
                TextEntry::make('full_name')->label('Nombres y apellidos'),
                TextEntry::make('document_number')->label('DNI / CE')->placeholder('—'),
                TextEntry::make('email')->label('Correo')->copyable(),
                TextEntry::make('phone')->label('Teléfono')->placeholder('—'),
                TextEntry::make('subject')->label('Asunto')->columnSpanFull(),
                TextEntry::make('message')->label('Mensaje')->columnSpanFull()->prose(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_read')
                    ->label('')
                    ->state(fn (ContactMessage $record): bool => $record->read_at !== null)
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedEnvelopeOpen)
                    ->falseIcon(Heroicon::OutlinedEnvelope)
                    ->trueColor('gray')
                    ->falseColor('warning'),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('full_name')->label('Remitente')->searchable(['full_name', 'email'])
                    ->description(fn (ContactMessage $record): string => $record->email),
                TextColumn::make('subject')->label('Asunto')->searchable()->limit(60)
                    ->weight(fn (ContactMessage $record): ?string => $record->read_at ? null : 'bold'),
                TextColumn::make('created_at')->label('Recibido')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(ContactMessageType::class),
                TernaryFilter::make('read_at')->label('Leído')->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markAsRead')
                    ->label('Marcar leído')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (ContactMessage $record): bool => $record->read_at === null)
                    ->authorize('update')
                    ->action(fn (ContactMessage $record) => $record->update(['read_at' => now()])),
                Action::make('reply')
                    ->label('Responder')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('gray')
                    ->url(fn (ContactMessage $record): string => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: '.$record->subject)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markAsRead')
                        ->label('Marcar como leídos')
                        ->icon(Heroicon::OutlinedCheck)
                        ->authorize(fn (): bool => Gate::allows('update', ContactMessage::class))
                        ->action(fn (Collection $records) => ContactMessage::query()->whereKey($records->modelKeys())->unread()->update(['read_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
        ];
    }
}
