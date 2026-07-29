import { createApp } from 'vue'
import App from './App.vue'
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import { plugin, defaultConfig } from '@formkit/vue';
import { createPinia } from 'pinia';
import { usePopulateTrees } from './js/setuptrees';
import { usePreload } from './js/preload';
import { useMstrings } from './stores/mstrings';
import { moodleFetch } from '@/js/moodlefetch';
import '../src/assets/VueModal.css';
import '../src/assets/MyGrades.css';
import '../src/assets/accessibility.css';
import { bootstrapAccessibility } from './stores/accessibility';
import type { IMoodleString } from './js/Interfaces';
import VueAwesomePaginate from 'vue-awesome-paginate';

import 'daisyui/daisyui.css';
import 'daisyui/themes.css';
import "vue-awesome-paginate/dist/style.css";

// @ts-ignore
import { Modal } from '@kouts/vue-modal';

import customConfig from './js/formkit.config';

// 1. App Configuration & Toast Setup
const toastoptions = {
    position: 'top-center' as const, // fixed typescript strict literal warning
    timeout: 5000,
};

// Apply any persisted accessibility profile to the document as early as
// possible so the chosen theme is active before the app renders (no flash).
bootstrapAccessibility();

const pinia = createPinia();
const app = createApp(App);

// 2. Register Global Plugins and Components
app.use(pinia);
app.use(Toast, toastoptions);
app.use(plugin, defaultConfig(customConfig));
app.use(VueAwesomePaginate);
app.component('VueModal', Modal);

// 3. Fetch strings and block mounting until they are loaded safely
const mstringstore = useMstrings();

moodleFetch('local_gugrades_get_all_strings', {})
    .then((result: any) => {
        const strings: IMoodleString[] = result;
        const parsedStrings: Record<string, string> = {};

        // Build the key-value dictionary cleanly
        strings.forEach((mstring: IMoodleString) => {
            parsedStrings[mstring.tag] = mstring.stringvalue;
        });

        // Save straight to your reactive Pinia store
        mstringstore.mstrings = parsedStrings;
    })
    .catch((error) => {
        window.console.error('Critical Error: Failed to fetch application strings:', error);
    })
    .finally(() => {
        // 4. NOW it is safe to mount the app and start background tasks
        app.mount('#app');

        // Populate activity trees
        //const populatetrees = usePopulateTrees();
        //populatetrees.populate();

        // Preload aggregation recalculations
        const preload = usePreload();
        preload.recalculate();
    });
