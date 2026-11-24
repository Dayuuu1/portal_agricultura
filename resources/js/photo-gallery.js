import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';
import { motionAllowed } from './motion';

const downloadIcon = '<path id="pswp__icn-download" d="M20.5 14.3 17.1 18V10h-2.2v7.9l-3.4-3.6L10 16l6 6.1 6-6.1ZM23 23H9v2h14Z"/>';

/**
 * Visor de fotos de las noticias (posts/show.blade.php). Cada enlace [data-photo] dentro de
 * [data-photo-gallery] abre su foto en grande sobre la misma página: se pasa con las flechas, deslizando
 * o con el teclado, se acerca con un clic o pellizcando, y se cierra con Esc o arrastrando hacia abajo.
 * Sin JavaScript el enlace abre la imagen. Con «Detener animaciones» o «prefers-reduced-motion» abre sin zoom animado.
 */
export default function initPhotoGalleries() {
    document.querySelectorAll('[data-photo-gallery]').forEach((gallery) => {
        const lightbox = new PhotoSwipeLightbox({
            gallery,
            children: 'a[data-photo]',
            pswpModule: () => import('photoswipe'),
            mainClass: 'pswp--portal',
            bgOpacity: 0.96,
            paddingFn: (viewportSize) => (viewportSize.x < 768 ? { top: 56, bottom: 16, left: 0, right: 0 } : { top: 64, bottom: 40, left: 72, right: 72 }),
            closeTitle: 'Cerrar (Esc)',
            zoomTitle: 'Acercar o alejar',
            arrowPrevTitle: 'Foto anterior',
            arrowNextTitle: 'Foto siguiente',
            errorMsg: 'No se pudo cargar esta foto.',
            indexIndicatorSep: ' de ',
        });

        lightbox.on('beforeOpen', () => {
            if (! motionAllowed()) {
                lightbox.pswp.options.showHideAnimationType = 'none';
                lightbox.pswp.options.zoomAnimationDuration = 0;
            }
        });

        // Si el servidor no pudo leer el tamaño de la foto, se toma de la miniatura ya cargada.
        lightbox.addFilter('itemData', (itemData) => {
            const thumbnail = itemData.element?.querySelector('img');

            if (! itemData.width && thumbnail?.naturalWidth) {
                itemData.width = thumbnail.naturalWidth;
                itemData.height = thumbnail.naturalHeight;
            }

            return itemData;
        });

        lightbox.on('uiRegister', () => {
            lightbox.pswp.ui.registerElement({
                name: 'download-button',
                title: 'Descargar la foto',
                ariaLabel: 'Descargar la foto',
                order: 8,
                isButton: true,
                tagName: 'a',
                html: { isCustomSVG: true, inner: downloadIcon, outlineID: 'pswp__icn-download' },
                onInit: (element, pswp) => {
                    element.setAttribute('download', '');
                    element.setAttribute('target', '_blank');
                    element.setAttribute('rel', 'noopener');

                    pswp.on('change', () => {
                        element.href = pswp.currSlide.data.src;
                    });
                },
            });
        });

        lightbox.init();
    });
}
