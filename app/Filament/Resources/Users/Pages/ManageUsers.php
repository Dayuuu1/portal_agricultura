<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): string
    {
        return 'Cuentas del personal que administra el portal. Asigne a cada persona solo los roles que necesita para su trabajo y desactive la cuenta cuando deje el cargo.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Crear usuario'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'activas' => Tab::make('Activas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(User::query()->where('is_active', true)->count()),
            'inactivas' => Tab::make('Desactivadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(User::query()->where('is_active', false)->count())
                ->badgeColor('gray'),
        ];
    }
}
