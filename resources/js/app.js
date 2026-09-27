import './bootstrap';
import Alpine from 'alpinejs';
import lineItems from './line-items';
import register, { clearRegisterCart } from './register';

window.Alpine = Alpine;
window.clearRegisterCart = clearRegisterCart;
Alpine.data('lineItems', lineItems);
Alpine.data('register', register);
Alpine.start();
