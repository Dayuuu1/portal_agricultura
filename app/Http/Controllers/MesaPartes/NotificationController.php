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

        $notification->markAsRead();

        return view('mesa-partes.notifications.show', ['notification' => $notification->load('submission')]);
    }

    private function citizen(Request $request): Citizen
    {
        /** @var Citizen */
        return $request->user('citizen');
    }
}
