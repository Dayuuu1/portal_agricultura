<?php

namespace App\Http\Controllers\MesaPartes;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('mesa-partes.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(
            ['email' => ['required', 'email'], 'password' => ['required', 'string']],
            ['email.required' => 'Ingrese su correo electrónico.', 'email.email' => 'Ingrese un correo electrónico válido.', 'password.required' => 'Ingrese su contraseña.'],
        );

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Vuelva a intentarlo en '.RateLimiter::availableIn($throttleKey).' segundos.',
            ]);
        }

        if (! Auth::guard('citizen')->attempt(['email' => Str::lower($credentials['email']), 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var Citizen $citizen */
        $citizen = Auth::guard('citizen')->user();
        $citizen->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended(route('mesa-partes.account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('citizen')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mesa-partes.index')->with('status', 'Cerró su sesión de la Mesa de Partes Virtual.');
    }
}
