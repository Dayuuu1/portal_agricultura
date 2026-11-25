/**
 * Ventana de anuncios al ingresar al portal (components/site/announcements.blade.php).
 *
 * Recuerda, solo en el navegador del visitante, qué anuncios ya cerró, según la frecuencia elegida en el panel:
 * «una sola vez» y «una vez al día» en localStorage, «en cada visita» en sessionStorage. Cada anuncio lleva su
 * versión (fecha de la última edición), así que al modificarlo vuelve a aparecer para todos.
 *
 * Usa <dialog> nativo: el resto de la página queda inerte, Esc lo cierra y el foco vuelve a donde estaba.
 * Espera a que la página se pinte y a que cargue la primera imagen, para no abrirse vacío ni a saltos.
 *
 * Avisa al portal (AnnouncementTrackingController) cuando cada anuncio se muestra en pantalla, cuando se pulsa su
 * botón y cuando se cierra la ventana sin entrar. Solo se envían el anuncio y el hecho, nunca datos del visitante;
 * la vista previa del panel no se cuenta.
 */
const STORAGE_KEY = 'dramdd:anuncios';
const OPEN_DELAY = 900;
const IMAGE_TIMEOUT = 2500;

function storageFor(frequency) {
    return frequency === 'visit' ? window.sessionStorage : window.localStorage;
}

function readSeen(storage) {
    try {
        return JSON.parse(storage.getItem(STORAGE_KEY) ?? '{}') ?? {};
    } catch {
        return {};
    }
}

function writeSeen(storage, seen) {
    try {
        storage.setItem(STORAGE_KEY, JSON.stringify(seen));
    } catch {
        // Navegación privada o almacenamiento bloqueado: el anuncio volverá a aparecer en la próxima página.
    }
}

function today() {
    const now = new Date();

    return `${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()}`;
}

export default function announcements(entries, preview = false, token = '') {
    return {
        entries,
        preview,
        /** Índices (en entries) de los anuncios que este visitante todavía debe ver. */
        pending: [],
        index: 0,
        /** Anuncios ya contados como vistos o pulsados en esta apertura, para no contarlos dos veces. */
        viewed: [],
        clicked: [],

        init() {
            this.pending = preview
                ? [0]
                : entries.flatMap((entry, position) => (this.isDue(entry) ? [position] : []));

            if (this.pending.length > 0) {
                setTimeout(() => this.open(), preview ? 0 : OPEN_DELAY);
            }
        },

        isDue(entry) {
            const seen = readSeen(storageFor(entry.frequency))[entry.id];

            if (! seen || seen.version !== entry.version) {
                return true;
            }

            return entry.frequency === 'daily' && seen.day !== today();
        },

        isCurrent(position) {
            return this.pending[this.index] === position;
        },

        async open() {
            const dialog = this.$refs.dialog;

            // Otra ventana del portal abierta (visor de fotos): el anuncio espera a la próxima página.
            if (dialog.open || document.querySelector('dialog[open], .pswp--open')) {
                return;
            }

            await this.imageReady(this.pending[0]);
            dialog.showModal();
            this.countView();
        },

        /**
         * The images are lazy so visitors who already closed the window never download them; on opening they
         * all start loading, and the first one gets a moment to arrive.
         */
        async imageReady(position) {
            this.$refs.dialog.querySelectorAll('img[loading="lazy"]').forEach((image) => (image.loading = 'eager'));

            const image = this.$refs.dialog.querySelector(`[data-announcement="${position}"] img`);

            if (! image || image.complete) {
                return;
            }

            await Promise.race([
                image.decode().catch(() => {}),
                new Promise((resolve) => setTimeout(resolve, IMAGE_TIMEOUT)),
            ]);
        },

        close() {
            this.$refs.dialog.close();
        },

        go(position) {
            this.index = (position + this.pending.length) % this.pending.length;
            this.countView();
        },

        next() {
            this.go(this.index + 1);
        },

        previous() {
            this.go(this.index - 1);
        },

        /** The visitor followed the button (or the poster) of the announcement on screen. */
        follow() {
            const position = this.pending[this.index];

            if (! this.clicked.includes(position)) {
                this.clicked.push(position);
                this.track(position, 'click');
            }

            this.remember();
        },

        /** Native «close» event: the button, Esc or a click outside. Counts as dismissed unless the visitor went in. */
        closed() {
            const position = this.pending[this.index];

            if (position !== undefined && ! this.clicked.includes(position)) {
                this.track(position, 'dismiss');
            }

            this.remember();
        },

        countView() {
            const position = this.pending[this.index];

            if (position !== undefined && ! this.viewed.includes(position)) {
                this.viewed.push(position);
                this.track(position, 'view');
            }
        },

        /**
         * sendBeacon survives leaving the page (a click on the button navigates away at once);
         * fetch with keepalive is the fallback. A failed report never bothers the visitor.
         */
        track(position, event) {
            const entry = this.entries[position];

            if (this.preview || ! entry?.track) {
                return;
            }

            const body = new FormData();
            body.append('_token', token);
            body.append('event', event);

            try {
                if (navigator.sendBeacon?.(entry.track, body)) {
                    return;
                }
            } catch {
                // Algunos navegadores rechazan el envío; se intenta con fetch.
            }

            fetch(entry.track, { method: 'POST', body, keepalive: true, credentials: 'same-origin' }).catch(() => {});
        },

        /** Closing (or following a link) counts for every announcement of the window, not only the one on screen. */
        remember() {
            if (this.preview) {
                return;
            }

            const day = today();

            this.pending.forEach((position) => {
                const entry = this.entries[position];
                const storage = storageFor(entry.frequency);
                const seen = readSeen(storage);

                seen[entry.id] = { version: entry.version, day };
                writeSeen(storage, seen);
            });
        },
    };
}
