require('./bootstrap');

import Vue from 'vue';
import moment from 'moment';
import VueMoment from 'vue-moment';

window.moment = moment;
window.Vue = Vue;


Vue.use(VueMoment, {moment});

Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue').default
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue').default
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue').default
);

const app = new Vue({
    el: '#app'
});