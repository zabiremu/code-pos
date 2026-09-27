import './bootstrap';
import Alpine from 'alpinejs';
import lineItems from './line-items';

window.Alpine = Alpine;
Alpine.data('lineItems', lineItems);
Alpine.start();
