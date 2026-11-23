<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Sends to the two-step verification setup only the staff the policy of «Correo y acceso» covers
 * (nobody, administrators or everyone); the rest of the panel works as before.
 */
class RequireMultiFactorAuthenticationByPolicy extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->mustUseMultiFactorAuthentication()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
