import Alpine from 'alpinejs';
import eventForm from './admin/event-form';

Alpine.data('eventForm', eventForm);

window.Alpine = Alpine;
Alpine.start();
