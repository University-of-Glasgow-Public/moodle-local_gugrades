import { createApp, reactive, ref } from 'vue'
import App from './App.vue'
import Toast  from "vue-toastification";
import "vue-toastification/dist/index.css";
import 'vue3-easy-data-table/dist/style.css';
import { plugin, defaultConfig } from '@formkit/vue';
import { createPinia } from 'pinia';
import { usePopulateTrees } from './js/setuptrees';
import { usePreload } from './js/preload';
import { useMstrings } from './stores/mstrings';
import { moodleFetch } from '@/js/moodlefetch';
import './assets/VueModal.css';
import './assets/MyGrades.css';
import type { IMoodleString } from './js/Interfaces';
import VueAwesomePaginate from 'vue-awesome-paginate';

// Following work but cause trouble with Typescript.
// Something to improve another day.
// @ts-ignore
import Vue3EasyDataTable from 'vue3-easy-data-table';
// @ts-ignore
import { Modal } from '@kouts/vue-modal';

import customConfig from './js/formkit.config';

// This stuff makes sure that the window.GU variable
// exists.
// This can take some time as Moodle runs this once the page
// has loaded
var timeout = 1000000;

/*
function ensureGUIsSet(timeout: number) {
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
*/

/**
 * Updated, even more inscruitable AI version.
 * @param timeout
 * @returns
 */
function ensureGUIsSet(timeout: number): Promise<typeof window.GU> {
    const start = Date.now();

    return new Promise(waitForGU);

    function waitForGU(
        resolve: (value: typeof window.GU) => void,
        reject: (reason?: Error) => void
    ): void {
        if (window.GU) {
            resolve(window.GU);
        } else if ((Date.now() - start) >= timeout) {
            reject(new Error("timeout"));
        } else {
            setTimeout(waitForGU.bind(null, resolve, reject), 30);
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
    const mstrings = ref<Record<string, string>>({});
    app.provide('mstrings', mstrings);
    app.use(Toast, toastoptions);
    app.use(plugin, defaultConfig(customConfig));
    app.use(VueAwesomePaginate);
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
    .then((result: any) => {
        const strings: IMoodleString[] = result;
        strings.forEach((mstring: IMoodleString) => {
            mstrings.value[mstring.tag] = mstring.stringvalue;
        });
        mstringstore.mstrings = mstrings.value;
    })
    .catch((error) => {
        window.console.error(error);
    });

    // Populate activity trees.
    populatetrees.populate();

    // Preload aggregation recalculations.
    preload.recalculate();

});


