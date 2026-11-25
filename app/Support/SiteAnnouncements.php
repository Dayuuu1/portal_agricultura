<?php

namespace App\Support;

use App\Enums\AnnouncementPlacement;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Announcements the welcome window shows on the current page (components/site/announcements.blade.php).
 * The browser decides which ones the visitor has already closed (resources/js/announcements.js).
 */
final class SiteAnnouncements
{
    /**
     * Query parameter that lets panel staff open any announcement, even a disabled one, to check how it looks.
     */
    public const PREVIEW_PARAMETER = 'anuncio';

    /**
     * More than this in one window stops being an announcement and becomes noise.
     */
    private const LIMIT = 5;

    /**
     * Forms with legal effect: a window over them would interrupt a citizen in the middle of a procedure.
     */
    private const EXCLUDED_ROUTES = ['mesa-partes.*', 'complaints.*', 'contact.*'];

    /**
     * @return array{announcements: Collection<int, Announcement>, preview: bool}
     */
    public static function for(Request $request): array
    {
        if ($request->routeIs(...self::EXCLUDED_ROUTES)) {
            return ['announcements' => new Collection, 'preview' => false];
        }

        if ($preview = self::preview($request)) {
            return ['announcements' => new Collection([$preview]), 'preview' => true];
        }

        $announcements = Announcement::query()
            ->active()
            ->with('post')
            ->when(! $request->routeIs('home'), fn (Builder $query) => $query->where('placement', AnnouncementPlacement::Everywhere))
            ->orderBy('sort_order')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get()
            // Whoever is already reading the highlighted page does not need a window inviting them to it.
            ->reject(fn (Announcement $announcement): bool => ($link = $announcement->link()) !== null && url($link) === $request->url())
            ->values();

        return ['announcements' => $announcements, 'preview' => false];
    }

    private static function preview(Request $request): ?Announcement
    {
        $id = $request->integer(self::PREVIEW_PARAMETER);

        if ($id < 1 || ! $request->user()?->can('viewAny', Announcement::class)) {
            return null;
        }

        return Announcement::query()->with('post')->find($id);
    }
}
