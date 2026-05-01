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
import '../src/assets/VueModal.css';
import '../src/assets/MyGrades.css';
import type { IMoodleString } from './js/Interfaces';
import VueAwesomePaginate from 'vue-awesome-paginate';

import 'daisyui/daisyui.css';
import 'daisyui/themes.css';

// Following work but cause trouble with Typescript.
// Something to improve another day.
// @ts-ignore
import Vue3EasyDataTable from 'vue3-easy-data-table';
// @ts-ignore
import { Modal } from '@kouts/vue-modal';

import customConfig from './js/formkit.config';

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

const app = createApp(App);
app.use(pinia);
const mstrings = ref<Record<string, string>>({});
//app.provide('mstrings', mstrings);
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



