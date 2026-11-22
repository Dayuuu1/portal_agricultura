<?php

namespace App\Http\Controllers\MesaPartes;

use App\Enums\IdentityDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MesaPartes\RegisterCitizenRequest;
use App\Models\Citizen;
use App\Support\MesaPartes\MesaPartesConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('mesa-partes.auth.register', [
            'documentTypes' => IdentityDocumentType::cases(),
            'departments' => MesaPartesConfig::DEPARTMENTS,
            'allowsLegalEntities' => MesaPartesConfig::allowsLegalEntities(),
        ]);
    }

    public function store(RegisterCitizenRequest $request): RedirectResponse
    {
        $now = now();

        $citizen = Citizen::query()->create([
            ...$request->safe()->except(['privacy', 'truthful', 'e_notifications', 'website', 'password_confirmation']),
            'privacy_accepted_at' => $now,
            'truthfulness_declared_at' => $now,
            'e_notifications_authorized_at' => $request->boolean('e_notifications') ? $now : null,
        ]);

        try {
            $citizen->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);
        }

        Auth::guard('citizen')->login($citizen);
        $request->session()->regenerate();

        return redirect()->route('mesa-partes.verification.notice');
    }
}
