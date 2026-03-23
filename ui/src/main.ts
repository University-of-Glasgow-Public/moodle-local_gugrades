import { createApp, reactive } from 'vue'
import App from './App.vue'
import Toast  from "vue-toastification";
import "vue-toastification/dist/index.css";
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import { plugin, defaultConfig } from '@formkit/vue';
import { Modal } from '@kouts/vue-modal';
import { createPinia } from 'pinia';
import { usePopulateTrees } from './js/setuptrees.js';
import { usePreload } from './js/preload.js';
import { useMstrings } from './stores/mstrings';
import { moodleFetch } from '@/js/moodlefetch';
import '../src/assets/VueModal.css';
import '../src/assets/MyGrades.css';

import customConfig from '../formkit.config.js';

declare global {
  interface Window {
    GU: object;
    fetchMany: object;
  }
}

// This stuff makes sure that the window.GU variable
// exists.
// This can take some time as Moodle runs this once the page
// has loaded
var timeout = 1000000;

function ensureGUIsSet(timeout) {
    var start = Date.now();
    return new Promise(waitForGU);


    function waitForGU(resolve, reject) {
        if (window.GU) {
            resolve(window.GU)
        } else if (timeout && (Date.now() - start) >= timeout) {
            reject(new Error("timeout"));
        } else {
            setTimeout(waitForGU.bind(this, resolve, reject), 30);
        }
    }
}

// Toast defaults
const toastoptions = {
    position: 'top-center',
    timeout: 5000,
};

// Pinia.
const pinia = createPinia();

// Trees.
const populatetrees = usePopulateTrees();

// MStrings - moving from provide/inject to Pinia.
// Currently using both.


// Preload
const preload = usePreload();

ensureGUIsSet(timeout)
.then(() => {
    const app = createApp(App);
    app.use(pinia);
    const mstrings = reactive([]);
    app.provide('mstrings', mstrings);
    app.use(Toast, toastoptions);
    app.use(plugin, defaultConfig(customConfig));
    app.component('EasyDataTable', Vue3EasyDataTable);
    app.component('VueModal', Modal);
    app.mount('#app');

    // Read strings
    // Strings are pushed to individual components using provide() / inject(
    const mstringstore = useMstrings();

    moodleFetch(
        'local_gugrades_get_all_strings',
        {}
    )
    .then((result) => {
        const strings = result;
        strings.forEach((string) => {
            mstrings[string.tag] = string.stringvalue;
        });
        mstringstore.mstrings = mstrings;
    })
    .catch((error) => {
        window.console.error(error);
    });

    // Populate activity trees.
    populatetrees.populate();

    // Preload aggregation recalculations.
    preload.recalculate();

});


