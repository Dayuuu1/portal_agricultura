<?php

namespace App\Http\Controllers\MesaPartes;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('mesa-partes.auth.forgot-password');
    }

    /**
     * Always answers the same, so the form does not reveal which e-mails have an account.
     */
    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], ['email.required' => 'Ingrese su correo electrónico.', 'email.email' => 'Ingrese un correo electrónico válido.']);

        Password::broker('citizens')->sendResetLink(['email' => Str::lower($request->string('email')->toString())]);

        return back()->with('status', 'Si el correo tiene una cuenta, le llegará un enlace para restablecer la contraseña en los próximos minutos.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('mesa-partes.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'email.required' => 'Ingrese su correo electrónico.',
            'password.required' => 'Ingrese la nueva contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.letters' => 'La contraseña debe incluir letras.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $status = Password::broker('citizens')->reset(
            [...$request->only('password', 'password_confirmation', 'token'), 'email' => Str::lower($request->string('email')->toString())],
            function (Citizen $citizen, string $password): void {
                $citizen->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('mesa-partes.login')->with('status', 'Su contraseña fue actualizada. Ingrese con la nueva contraseña.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'El enlace no es válido o ya venció. Solicite uno nuevo.']);
    }
}
