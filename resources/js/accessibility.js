/**
 * Barra de accesibilidad del portal (Ley N° 29973, WCAG 2.1).
 * Las preferencias se guardan solo en el navegador del visitante y se aplican
 * antes de pintar la página mediante window.applyAccessibility (ver layout).
 */
const STORAGE_KEY = 'dramdd:accesibilidad';
const MIN_SCALE = 80;
const MAX_SCALE = 200;
const STEP = 10;

const DEFAULTS = Object.freeze({
    fontScale: 100,
    contrast: false,
    grayscale: false,
    links: false,
    dyslexia: false,
    noMotion: false,
});

const LABELS = {
    contrast: 'Alto contraste',
    grayscale: 'Escala de grises',
    links: 'Resaltar enlaces',
    dyslexia: 'Fuente para dislexia',
    noMotion: 'Detener animaciones',
};

function readStored() {
    try {
        return { ...DEFAULTS, ...JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? '{}') };
    } catch {
        return { ...DEFAULTS };
    }
}

/**
 * Splits long texts into short utterances: some browsers stop speaking after ~15 seconds.
 */
function chunkText(text, maxLength = 220) {
    const sentences = text.replace(/\s+/g, ' ').trim().match(/[^.!?;:]+[.!?;:]*\s*/g) ?? [];
    const chunks = [];
    let current = '';

    for (const sentence of sentences) {
        if ((current + sentence).length > maxLength && current !== '') {
            chunks.push(current.trim());
            current = '';
        }

        current += sentence;
    }

    if (current.trim() !== '') {
        chunks.push(current.trim());
    }

    return chunks;
}

export default function accessibility() {
    return {
        open: false,
        settings: readStored(),
        announcement: '',
        speaking: false,
        paused: false,
        rememberedSelection: '',
        canSpeak: 'speechSynthesis' in window,

        init() {
            this.$watch('settings', () => this.persist());

            if (this.canSpeak) {
                window.speechSynthesis.getVoices();
                window.addEventListener('beforeunload', () => window.speechSynthesis.cancel());
            }
        },

        toggle() {
            this.open ? this.close() : this.show();
        },

        show() {
            this.open = true;
            this.$nextTick(() => this.$refs.panel.focus());
        },

        close(returnFocus = true) {
            if (! this.open) {
                return;
            }

            this.open = false;

            if (returnFocus) {
                this.$nextTick(() => this.$refs.trigger.focus());
            }
        },

        onKeydown(event) {
            if (event.altKey && (event.key === '0' || event.code === 'Digit0' || event.code === 'Numpad0')) {
                event.preventDefault();
                this.toggle();
            }
        },

        persist() {
            window.applyAccessibility?.(this.settings);

            try {
                window.localStorage.setItem(STORAGE_KEY, JSON.stringify(this.settings));
            } catch {
                // Navegación privada o almacenamiento bloqueado: la preferencia dura solo esta visita.
            }

            if (this.settings.noMotion) {
                window.dispatchEvent(new CustomEvent('accesibilidad:detener-animaciones'));
            }
        },

        announce(message) {
            this.announcement = '';
            this.$nextTick(() => (this.announcement = message));
        },

        changeFontScale(direction) {
            const scale = direction === 0
                ? 100
                : Math.min(MAX_SCALE, Math.max(MIN_SCALE, this.settings.fontScale + direction * STEP));

            this.settings.fontScale = scale;
            this.announce(`Tamaño del texto: ${scale} %`);
        },

        toggleOption(option) {
            this.settings[option] = ! this.settings[option];
            this.announce(`${LABELS[option]} ${this.settings[option] ? 'activado' : 'desactivado'}`);
        },

        rememberSelection() {
            this.rememberedSelection = window.getSelection()?.toString().trim() ?? '';
        },

        speak() {
            if (! this.canSpeak) {
                this.announce('Su navegador no permite la lectura en voz alta.');

                return;
            }

            const synth = window.speechSynthesis;
            const text = this.rememberedSelection || document.getElementById('contenido')?.innerText || '';
            const chunks = chunkText(text);
            this.rememberedSelection = '';

            synth.cancel();

            if (chunks.length === 0) {
                this.announce('No hay texto para leer.');

                return;
            }

            const voices = synth.getVoices();
            const voice = ['es-PE', 'es-419', 'es-US', 'es-MX', 'es-ES']
                .map((lang) => voices.find((candidate) => candidate.lang.replace('_', '-') === lang))
                .find(Boolean) ?? voices.find((candidate) => candidate.lang.startsWith('es'));

            chunks.forEach((chunk, index) => {
                const utterance = new SpeechSynthesisUtterance(chunk);
                utterance.lang = voice?.lang ?? 'es-PE';
                utterance.voice = voice ?? null;
                utterance.rate = 0.95;

                if (index === chunks.length - 1) {
                    utterance.onend = () => {
                        this.speaking = false;
                        this.paused = false;
                    };
                }

                synth.speak(utterance);
            });

            this.speaking = true;
            this.paused = false;
        },

        togglePause() {
            if (! this.speaking) {
                return;
            }

            if (this.paused) {
                window.speechSynthesis.resume();
            } else {
                window.speechSynthesis.pause();
            }

            this.paused = ! this.paused;
        },

        stopSpeaking() {
            if (this.canSpeak) {
                window.speechSynthesis.cancel();
            }

            this.speaking = false;
            this.paused = false;
        },

        reset() {
            this.stopSpeaking();
            this.settings = { ...DEFAULTS };

            try {
                window.localStorage.removeItem(STORAGE_KEY);
            } catch {
                // Sin almacenamiento disponible: no hay nada que borrar.
            }

            this.announce('Se restableció la configuración de accesibilidad.');
        },
    };
}
