<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getSubheading(): string
    {
        return 'Mensajes enviados desde el formulario de contacto del portal. Ábralos para leerlos, responda por correo con «Responder» y márquelos como leídos.';
    }

    public function getTabs(): array
    {
        return [
            'sin-leer' => Tab::make('Sin leer')
                ->modifyQueryUsing(fn (Builder $query) => $query->unread())
                ->badge(ContactMessage::query()->unread()->count())
                ->badgeColor('warning'),
            'todos' => Tab::make('Todos')->badge(ContactMessage::query()->count()),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return ContactMessage::query()->unread()->exists() ? 'sin-leer' : 'todos';
    }
}
