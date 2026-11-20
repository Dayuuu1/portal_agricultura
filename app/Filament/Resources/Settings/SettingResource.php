<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Settings\Pages\EditSettings;
use App\Models\Setting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * «Datos del portal»: a single page where every value is edited at once (see EditSettings).
 */
class SettingResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Setting::class;

    protected static ?string $slug = 'datos-del-portal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Datos del portal';

    protected static ?string $modelLabel = 'dato del portal';

    protected static ?string $pluralModelLabel = 'datos del portal';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => EditSettings::route('/'),
        ];
    }
}
