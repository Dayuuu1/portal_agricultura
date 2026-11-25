import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import accessibility from './accessibility';
import initAnalytics from './analytics';
import announcements from './announcements';
import heroCarousel from './hero-carousel';
import initMotion from './motion';

Alpine.plugin(collapse);
Alpine.data('accessibility', accessibility);
Alpine.data('announcements', announcements);
Alpine.data('heroCarousel', heroCarousel);

window.Alpine = Alpine;
Alpine.start();

initMotion();
initAnalytics();

// El visor de fotos solo se descarga en las páginas que tienen fotos ampliables.
if (document.querySelector('[data-photo-gallery]')) {
    import('./photo-gallery').then(({ default: initPhotoGalleries }) => initPhotoGalleries());
}
