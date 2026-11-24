/**
 * Capa de movimiento e interacción del portal público.
 * Respeta «prefers-reduced-motion» y la opción «Detener animaciones» (html.a11y-no-motion):
 * en esos casos el contenido se muestra directamente, sin transiciones.
 */
const root = document.documentElement;
const numberFormat = new Intl.NumberFormat('es-PE');

export function motionAllowed() {
    return ! window.matchMedia('(prefers-reduced-motion: reduce)').matches && ! root.classList.contains('a11y-no-motion');
}

/**
 * Bloques con [data-reveal] aparecen al entrar en pantalla (una sola vez).
 */
function initReveal() {
    const elements = document.querySelectorAll('[data-reveal]');

    if (! ('IntersectionObserver' in window) || ! motionAllowed()) {
        root.classList.remove('js-motion');

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

    elements.forEach((element) => observer.observe(element));
}

/**
 * Cifras con [data-count-to] cuentan desde cero al hacerse visibles.
 * El HTML ya trae la cifra final: sin JavaScript o sin movimiento se ve el número correcto.
 */
function initCounters() {
    const counters = document.querySelectorAll('[data-count-to]');

    if (! motionAllowed() || ! ('IntersectionObserver' in window)) {
        return;
    }

    const animate = (element) => {
        const target = Number(element.dataset.countTo);
        const duration = 1600;
        const start = performance.now();

        const step = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - progress, 4);
            element.textContent = numberFormat.format(Math.round(target * eased));

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animate(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.6 });

    counters.forEach((element) => {
        element.textContent = numberFormat.format(0);
        observer.observe(element);
    });
}

/**
 * La cabecera gana sombra al desplazarse y aparece el botón «Volver arriba».
 */
function initScrollState() {
    const header = document.querySelector('[data-site-header]');
    const backToTop = document.querySelector('[data-back-to-top]');
    let ticking = false;

    const update = () => {
        const y = window.scrollY;
        header?.toggleAttribute('data-scrolled', y > 120);
        backToTop?.toggleAttribute('data-visible', y > 700);
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (! ticking) {
            requestAnimationFrame(update);
            ticking = true;
        }
    }, { passive: true });

    backToTop?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: motionAllowed() ? 'smooth' : 'auto' });
        document.getElementById('contenido')?.focus({ preventScroll: true });
    });

    update();
}

/**
 * Las imágenes que no cargan (archivo pendiente o enlace roto) se ocultan y dejan ver el fondo
 * del marco (.media) en lugar del ícono de imagen rota.
 */
function initImageFallbacks() {
    const markBroken = (image) => image.classList.add('is-broken');

    document.addEventListener('error', (event) => {
        if (event.target instanceof HTMLImageElement) {
            markBroken(event.target);
        }
    }, true);

    // Imágenes que fallaron antes de que este script se ejecutara.
    document.querySelectorAll('img').forEach((image) => {
        if (image.complete && image.naturalWidth === 0 && image.getAttribute('src')) {
            markBroken(image);
        }
    });
}

/**
 * Atajo de teclado: «/» lleva al buscador de la cabecera (como en los buscadores habituales).
 */
function initSearchShortcut() {
    document.addEventListener('keydown', (event) => {
        if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        const target = event.target;

        if (target instanceof HTMLElement && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))) {
            return;
        }

        const input = document.getElementById('busqueda-cabecera');

        if (input && input.offsetParent !== null) {
            event.preventDefault();
            input.focus();
        }
    });
}

export default function initMotion() {
    window.__motionReady = true;

    initImageFallbacks();
    initReveal();
    initCounters();
    initScrollState();
    initSearchShortcut();

    // «Detener animaciones» desde la barra de accesibilidad: todo lo pendiente se muestra al instante.
    window.addEventListener('accesibilidad:detener-animaciones', () => {
        root.classList.remove('js-motion');
        document.querySelectorAll('[data-count-to]').forEach((element) => {
            element.textContent = numberFormat.format(Number(element.dataset.countTo));
        });
    });
}
