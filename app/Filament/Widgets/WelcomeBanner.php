<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

class WelcomeBanner extends Widget
{
    protected string $view = 'filament.widgets.welcome-banner';

    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{user: User, greeting: string, areas: list<string>}
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $hour = (int) now()->format('G');

        return [
            'user' => $user,
            'greeting' => match (true) {
                $hour < 12 => 'Buenos días',
                $hour < 19 => 'Buenas tardes',
                default => 'Buenas noches',
            },
            'areas' => $user->is_admin
                ? ['Administrador: acceso total']
                : $user->roles->pluck('name')->sort()->values()->all(),
        ];
    }
}
