/**
 * Libraries
 */
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.moment = require('moment');

/**
 * Auth module
 */
import Auth from './dashboard/Auth';
window.$auth = new Auth(window.$user ? window.$user : {});

/**
 * Vue.js
 */
window.Vue = require('vue');
window.Events = new Vue();
require('./dashboard/functions');

// Element UI
import ElementUI from 'element-ui';
import locale from 'element-ui/lib/locale/lang/en'
import 'element-ui/lib/theme-chalk/index.css';

Vue.use(ElementUI, { locale });

/**
 * Devel components
 */
// Form
Vue.component('v-form', require('./dashboard/form/Form').default);
Vue.component('v-form-tab', require('./dashboard/form/FormTab').default);
Vue.component('v-form-el', require('./dashboard/form/Element').default);

// Components
Vue.component('v-datatable', require('./dashboard/components/DataTable').default);
Vue.component('v-alert', require('./dashboard/components/Alert').default);
Vue.component('v-notification', require('./dashboard/components/Notification').default);
Vue.component('v-modal', require('./dashboard/components/Modal').default);

// Pages
Vue.component('v-pages-modules', require('./dashboard/pages/Modules').default);
