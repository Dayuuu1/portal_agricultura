<?php

namespace App\Http\Middleware;

use App\Support\MesaPartes\MesaPartesConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * New accounts and new documents are only accepted while the entity keeps the service enabled.
 */
class EnsureMesaPartesIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! MesaPartesConfig::enabled()) {
            return redirect()->route('mesa-partes.index')
                ->with('status', 'La Mesa de Partes Virtual no está recibiendo documentos en este momento.');
        }

        return $next($request);
    }
}
