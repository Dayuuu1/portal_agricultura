/**
 * Estadísticas propias del portal (AnalyticsController, App\Support\Analytics\PageViewRecorder).
 *
 * Al abrir una página avisa qué página es, desde dónde llegó el visitante y, si es un buscador, qué se buscó y
 * cuántos resultados hubo. Luego informa cuánto tiempo estuvo la pestaña visible, las descargas de documentos y
 * los enlaces a otros sitios. No usa cookies ni guarda nada en el navegador; el layout solo lo activa para
 * visitantes (no para el personal del panel ni para robots).
 */
const MAX_SECONDS = 1800;
const DOWNLOAD_PATH = /^\/documentos\/[^/]+\/descargar\/?$/;

export default function initAnalytics() {
    const endpoint = document.querySelector('meta[name="analytics-endpoint"]')?.content;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    // Navegadores automatizados (pruebas, robots que ejecutan JavaScript) no cuentan.
    if (! endpoint || ! token || navigator.webdriver) {
        return;
    }

    let reportUrl = null;
    let visibleSince = null;
    let engaged = 0;

    const send = (data) => {
        if (! reportUrl) {
            return;
        }

        const body = new FormData();
        body.append('_token', token);
        Object.entries(data).forEach(([key, value]) => body.append(key, value));

        try {
            if (navigator.sendBeacon?.(reportUrl, body)) {
                return;
            }
        } catch {
            // Algunos navegadores rechazan el envío; se intenta con fetch.
        }

        fetch(reportUrl, { method: 'POST', body, keepalive: true, credentials: 'same-origin' }).catch(() => {});
    };

    /** Tiempo con la pestaña visible: una pestaña olvidada en segundo plano no suma. */
    const reportTime = () => {
        if (visibleSince !== null) {
            engaged += performance.now() - visibleSince;
            visibleSince = null;
        }

        send({ action: 'leave', seconds: Math.min(MAX_SECONDS, Math.round(engaged / 1000)) });
    };

    const start = () => {
        const params = new URLSearchParams(window.location.search);
        const search = document.querySelector('[data-analytics-search]');

        visibleSince = document.visibilityState === 'visible' ? performance.now() : null;

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({
                path: window.location.pathname,
                title: document.title,
                referrer: document.referrer || null,
                utm_source: params.get('utm_source'),
                utm_campaign: params.get('utm_campaign'),
                width: window.screen?.width ?? window.innerWidth,
                search_term: search?.dataset.term ?? null,
                search_results: search ? Number(search.dataset.results) : null,
                search_scope: search?.dataset.scope ?? null,
                not_found: document.querySelector('[data-analytics-not-found]') !== null,
            }),
        })
            .then((response) => (response.status === 200 ? response.json() : null))
            .then((data) => {
                reportUrl = data?.url ?? null;
            })
            .catch(() => {});
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            reportTime();
        } else {
            visibleSince = performance.now();
        }
    });

    window.addEventListener('pagehide', reportTime);

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

        if (! link) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin === window.location.origin && DOWNLOAD_PATH.test(url.pathname)) {
            send({ action: 'download', target: url.pathname });
        } else if (/^https?:$/.test(url.protocol) && url.host !== window.location.host) {
            send({ action: 'outbound', target: url.href });
        }
    }, { capture: true });

    // Una página precargada por el navegador solo cuenta si el visitante llega a abrirla.
    if (document.prerendering) {
        document.addEventListener('prerenderingchange', start, { once: true });
    } else {
        start();
    }
}
