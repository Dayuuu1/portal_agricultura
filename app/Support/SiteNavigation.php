<?php

namespace App\Support;

use App\Enums\DocumentSection;
use App\Models\Agency;
use App\Models\DocumentType;
use App\Models\OrganizationalUnit;
use Illuminate\Support\Facades\Cache;

/**
 * Menu entries built from the organizational structure, so the public menu
 * follows whatever administrators register in the panel.
 */
final class SiteNavigation
{
    public const CACHE_KEY = 'portal.navigation';

    /**
     * @return array{
     *     offices: list<array{name: string, url: string, children: list<array{name: string, url: string}>}>,
     *     agencies: list<array{name: string, url: string}>,
     *     agrarianTypes: list<array{name: string, url: string}>
     * }
     */
    public static function data(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, self::build(...));
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{offices: list<array<string, mixed>>, agencies: list<array<string, string>>, agrarianTypes: list<array<string, string>>}
     */
    private static function build(): array
    {
        $link = fn (OrganizationalUnit $unit): array => ['name' => $unit->name, 'url' => route('institutional.unit', $unit, false)];
        $offices = [];

        $roots = OrganizationalUnit::query()->active()->whereNull('parent_id')->orderBy('sort_order')
            ->with(['children' => fn ($query) => $query->active()->with(['children' => fn ($inner) => $inner->active()])])
            ->get();

        foreach ($roots as $root) {
            $offices[] = [...$link($root), 'children' => []];

            foreach ($root->children as $office) {
                $offices[] = [...$link($office), 'children' => $office->children->map($link)->values()->all()];
            }
        }

        return [
            'offices' => $offices,
            'agencies' => Agency::query()->active()->orderBy('sort_order')->get()
                ->map(fn (Agency $agency): array => ['name' => $agency->name, 'url' => route('agencies.show', $agency, false)])
                ->all(),
            'agrarianTypes' => DocumentType::query()->where('section', DocumentSection::InformacionAgraria)->orderBy('sort_order')->get()
                ->map(fn (DocumentType $type): array => ['name' => $type->name, 'url' => route('documents.index', ['tipo' => $type->slug], false)])
                ->all(),
        ];
    }
}
