<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementStat;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AnnouncementTrackingController extends Controller
{
    /**
     * Counts what the announcement window reports: shown, button clicked or closed. Answers 204 either way, so the
     * endpoint never reveals whether something was counted; announcements no longer visible are ignored.
     */
    public function __invoke(Request $request, Announcement $announcement): Response
    {
        $validated = $request->validate([
            'event' => ['required', 'string', Rule::in(array_keys(AnnouncementStat::EVENTS))],
        ]);

        if (Announcement::query()->active()->whereKey($announcement->getKey())->exists()) {
            AnnouncementStat::record($announcement, $validated['event']);
        }

        return response()->noContent();
    }
}
