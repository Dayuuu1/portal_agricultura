import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import accessibility from './accessibility';
import heroCarousel from './hero-carousel';
import initMotion from './motion';

Alpine.plugin(collapse);
Alpine.data('accessibility', accessibility);
Alpine.data('heroCarousel', heroCarousel);

window.Alpine = Alpine;
Alpine.start();

initMotion();
