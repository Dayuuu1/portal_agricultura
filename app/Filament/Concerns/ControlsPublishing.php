<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Forms\Components\Toggle;

/**
 * For resources with an "is_published" switch: only users with the module's
 * "publish" permission can put content live; everyone else saves drafts.
 */
trait ControlsPublishing
{
    public static function canPublish(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('publish', static::getModel());
    }

    protected static function publishedToggle(string $label = 'Publicado', ?string $helperText = null): Toggle
    {
        return Toggle::make('is_published')
            ->label($label)
            ->default(fn (): bool => static::canPublish())
            ->disabled(fn (): bool => ! static::canPublish())
            ->helperText(fn (): ?string => static::canPublish()
                ? $helperText
                : 'Su rol no permite publicar: se guardará como borrador y una persona autorizada lo revisará y publicará.');
    }

    /**
     * Server-side guard for create/edit forms and bulk actions: a disabled switch is not
     * submitted, so without this the column default (published) would apply.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enforcePublishPermission(array $data, bool $creating): array
    {
        if (static::canPublish()) {
            return $data;
        }

        if ($creating) {
            $data['is_published'] = false;
        } else {
            unset($data['is_published']);
        }

        return $data;
    }
}
