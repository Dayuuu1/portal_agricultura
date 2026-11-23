/**
 * Banner principal de la portada. Avanza solo cada `interval` ms; la barra de la pestaña activa muestra
 * cuánto falta. Espera mientras el puntero está sobre un botón del banner o sobre los controles (un cambio
 * en ese momento haría pulsar otro enlace) y mientras se recorre con el teclado. El banner ocupa casi toda
 * la primera pantalla, así que el puntero apoyado sobre la foto o el titular no lo detiene.
 * Se queda quieto con «prefers-reduced-motion», con «Detener animaciones» o cuando el visitante lo pausa.
 */
export default function heroCarousel(total, interval = 7000) {
    return {
        active: 0,
        total,
        paused: false,
        hovering: false,
        focused: false,
        progress: 0,
        lastFrame: null,
        frame: null,

        init() {
            if (this.total < 2) {
                return;
            }

            this.frame = requestAnimationFrame((now) => this.tick(now));
            window.addEventListener('accesibilidad:detener-animaciones', () => (this.paused = true));
        },

        destroy() {
            cancelAnimationFrame(this.frame);
        },

        motionAllowed() {
            return ! window.matchMedia('(prefers-reduced-motion: reduce)').matches
                && ! document.documentElement.classList.contains('a11y-no-motion');
        },

        autoplays() {
            return this.total > 1 && this.motionAllowed();
        },

        playing() {
            return this.autoplays() && ! this.paused && ! this.hovering && ! this.focused && document.visibilityState === 'visible';
        },

        /** Keyboard focus only: a mouse click also focuses the button, and must not stop the carousel for good. */
        focusEntered(event) {
            this.focused = event.target instanceof Element && event.target.matches(':focus-visible');
        },

        focusLeft(event) {
            if (! this.$root.contains(event.relatedTarget)) {
                this.focused = false;
            }
        },

        tick(now) {
            if (this.lastFrame !== null && this.playing()) {
                // Capped so a background tab does not skip slides when it comes back.
                this.progress = Math.min(1, this.progress + Math.min(now - this.lastFrame, 100) / interval);

                if (this.progress >= 1) {
                    this.next();
                }
            }

            this.lastFrame = now;
            this.frame = requestAnimationFrame((time) => this.tick(time));
        },

        go(index) {
            this.active = (index + this.total) % this.total;
            this.progress = 0;
        },

        next() {
            this.go(this.active + 1);
        },

        prev() {
            this.go(this.active - 1);
        },

        togglePause() {
            this.paused = ! this.paused;
        },

        /** Fill of a tab's bar, from 0 to 1. */
        fill(index) {
            if (index !== this.active) {
                return 0;
            }

            return this.autoplays() ? this.progress : 1;
        },
    };
}
