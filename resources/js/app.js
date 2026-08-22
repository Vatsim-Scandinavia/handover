import * as bootstrap from 'bootstrap'
import Alpine from 'alpinejs';
import { createApp } from 'vue';
import AuthorizedClients from './components/passport/AuthorizedClients.vue';
import Clients from './components/passport/Clients.vue';
import axios from 'axios';

window.axios = axios;
window.bootstrap = bootstrap;
window.createApp = createApp;

axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]')?.content;

// Alpine powers small bits of view-only interactivity (e.g. the group
// page's edit toggle). It only activates inside x-data scopes, so it
// coexists with the Vue app mounted on #app below.
window.Alpine = Alpine;
Alpine.start();


const app = createApp({});

app.component('passport-authorized-clients', AuthorizedClients);
app.component('oauth-clients', Clients);

app.mount('#app');