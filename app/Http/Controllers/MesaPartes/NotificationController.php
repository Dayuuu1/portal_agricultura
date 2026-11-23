<?php

namespace App\Http\Controllers\MesaPartes;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\CitizenNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The citizen's inbox («casilla»). Opening a message records the reading date.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('mesa-partes.notifications.index', [
            'notifications' => $this->citizen($request)->inbox()->with('submission')->paginate(15),
        ]);
    }

    public function show(Request $request, CitizenNotification $notification): View
    {
        abort_unless($notification->citizen_id === $this->citizen($request)->id, 404);

        // The reading date is evidence of the notification, so a page the browser loads ahead of time does not count.
        if (! $this->isPrefetch($request)) {
            $notification->markAsRead();
        }

        return view('mesa-partes.notifications.show', ['notification' => $notification->load('submission')]);
    }

    private function isPrefetch(Request $request): bool
    {
        $purpose = strtolower($request->header('Sec-Purpose', '').' '.$request->header('Purpose', '').' '.$request->header('X-Moz', ''));

        return str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender');
    }

    private function citizen(Request $request): Citizen
    {
        /** @var Citizen */
        return $request->user('citizen');
    }
}
