<?php

namespace App\Http\Controllers\MesaPartes;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class VerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $this->citizen($request)->hasVerifiedEmail()
            ? redirect()->route('mesa-partes.account')
            : view('mesa-partes.auth.verify', ['citizen' => $this->citizen($request)]);
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $citizen = $this->citizen($request);

        abort_unless((string) $citizen->getKey() === $id && hash_equals(sha1($citizen->getEmailForVerification()), $hash), 403);

        if (! $citizen->hasVerifiedEmail()) {
            $citizen->markEmailAsVerified();
        }

        return redirect()->route('mesa-partes.account')->with('status', 'Su correo quedó confirmado. Ya puede presentar documentos.');
    }

    public function send(Request $request): RedirectResponse
    {
        $citizen = $this->citizen($request);

        if ($citizen->hasVerifiedEmail()) {
            return redirect()->route('mesa-partes.account');
        }

        try {
            $citizen->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', 'Le enviamos un nuevo enlace de confirmación a '.$citizen->email.'.');
    }

    private function citizen(Request $request): Citizen
    {
        /** @var Citizen */
        return $request->user('citizen');
    }
}
