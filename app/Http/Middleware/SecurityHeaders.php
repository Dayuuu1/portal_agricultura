<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser-side hardening for every page: no MIME sniffing, no framing by other sites,
 * no referrer leaks to third parties and no access to camera, microphone or location.
 */
class SecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Content-Security-Policy' => $this->contentSecurityPolicy(),
        ];

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        // The panel (its sign-in page included) and the reports it exports never belong in search results.
        if ($request->is('admin', 'admin/*', 'livewire/*')) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    /**
     * Second line of defence if some HTML slipped through: no <base> rewriting every link, no forms posting to
     * another site, no plugins other than the PDF previews of the portal's own files, no framing by others.
     * Scripts and styles are not restricted, because the panel (Livewire and Alpine) needs inline code.
     */
    private function contentSecurityPolicy(): string
    {
        $storageOrigin = $this->originOf((string) config('filesystems.disks.public.url'));

        return implode('; ', [
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            trim("object-src 'self' ".$storageOrigin),
        ]);
    }

    private function originOf(string $url): string
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
