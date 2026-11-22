<?php

namespace App\Http\Controllers\MesaPartes;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MesaPartes\UpdateCitizenProfileRequest;
use App\Models\Citizen;
use App\Support\MesaPartes\MesaPartesConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * «Mis trámites»: what needs the citizen's attention first, then every file with its status.
     */
    public function dashboard(Request $request): View
    {
        $citizen = $this->citizen($request);
        $submissions = $citizen->submissions()->with('procedureType')->latest('submitted_at')->latest('id')->paginate(10);

        return view('mesa-partes.account.dashboard', [
            'citizen' => $citizen,
            'submissions' => $submissions,
            'pendingObservations' => $citizen->submissions()->where('status', SubmissionStatus::Observado)->orderBy('cure_deadline')->get(),
            'counts' => [
                'open' => $citizen->submissions()->open()->count(),
                'observed' => $citizen->submissions()->where('status', SubmissionStatus::Observado)->count(),
                'answered' => $citizen->submissions()->where('status', SubmissionStatus::Atendido)->count(),
            ],
            'unread' => $citizen->inbox()->unread()->count(),
            'enabled' => MesaPartesConfig::enabled(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('mesa-partes.account.profile', [
            'citizen' => $this->citizen($request),
            'departments' => MesaPartesConfig::DEPARTMENTS,
        ]);
    }

    public function update(UpdateCitizenProfileRequest $request): RedirectResponse
    {
        $citizen = $this->citizen($request);
        $authorizes = $request->boolean('e_notifications');

        $citizen->update([
            ...$request->safe()->except('e_notifications'),
            'e_notifications_authorized_at' => $authorizes ? ($citizen->e_notifications_authorized_at ?? now()) : null,
        ]);

        return back()->with('status', 'Sus datos fueron actualizados.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:citizen'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Ingrese su contraseña actual.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.required' => 'Ingrese la nueva contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.letters' => 'La contraseña debe incluir letras.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $this->citizen($request)->update(['password' => $request->string('password')->toString()]);

        return back()->with('status', 'Su contraseña fue actualizada.');
    }

    private function citizen(Request $request): Citizen
    {
        /** @var Citizen */
        return $request->user('citizen');
    }
}
