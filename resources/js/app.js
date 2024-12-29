import * as bootstrap from 'bootstrap'
import moment from 'moment';
import { createApp } from 'vue';
import AuthorizedClients from './components/passport/AuthorizedClients.vue';
import Clients from './components/passport/Clients.vue';
import PersonalAccessTokens from './components/passport/PersonalAccessTokens.vue';
import axios from 'axios';

window.axios = axios;
window.bootstrap = bootstrap;
window.moment = moment;
window.createApp = createApp;


const app = createApp({});

app.component('passport-authorized-clients', AuthorizedClients);
app.component('passport-clients', Clients);
app.component('passport-personal-access-tokens', PersonalAccessTokens);

app.mount('#app');