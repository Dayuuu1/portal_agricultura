import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import accessibility from './accessibility';

Alpine.plugin(collapse);
Alpine.data('accessibility', accessibility);

window.Alpine = Alpine;
Alpine.start();
