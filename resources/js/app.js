import './bootstrap.js';
import './general-affair.js'
import './dashboard.js'
import './gantt-init.js'
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;

Alpine.start();
