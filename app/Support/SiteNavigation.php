<?php

namespace App\Support;

use App\Enums\DocumentSection;
use App\Models\Agency;
use App\Models\DocumentType;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Menu entries built from the organizational structure, so the public menu
 * follows whatever administrators register in the panel.
 */
final class SiteNavigation
{
    /**
     * Versioned so a change in the shape of the menu rebuilds it without clearing the cache by hand (v2 adds pages).
     */
    public const CACHE_KEY = 'portal.navigation.v3';

    /**
     * Icons for the pages the portal ships with; other pages get a generic document icon.
     */
    private const PAGE_ICONS = [
        'codigo-de-etica' => 'heroicon-o-shield-check',
        'politica-de-privacidad' => 'heroicon-o-lock-closed',
    ];

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
                ->map(fn (DocumentType $type): array => ['name' => $type->name, 'url' => $type->publicUrl(absolute: false)])
                ->all(),
            'pages' => Page::query()->inMenu()->orderBy('id')->get()
                ->map(fn (Page $page): array => [
                    'name' => $page->title,
                    'url' => route('pages.show', $page, false),
                    'hint' => Str::limit((string) $page->excerpt, 48),
                    'icon' => self::PAGE_ICONS[$page->slug] ?? 'heroicon-o-document-text',
                ])
                ->all(),
        ];
    }
}
