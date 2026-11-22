<?php

namespace App\Http\Middleware;

use App\Models\Citizen;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accounts suspended from the panel are signed out at their next request.
 */
class EnsureCitizenIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $citizen = $request->user('citizen');

        if ($citizen instanceof Citizen && ! $citizen->is_active) {
            Auth::guard('citizen')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('mesa-partes.login')
                ->withErrors(['email' => 'Su cuenta está suspendida. Comuníquese con la entidad para más información.']);
        }

        return $next($request);
    }
}
