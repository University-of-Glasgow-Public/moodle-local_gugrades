import { ref } from 'vue';
import { moodleFetch } from '@/js/moodlefetch';

const monochrome = ref(false);

export function useLogo() {

    const updateLogo = () => {

        moodleFetch(
            'local_gugrades_get_dashboard_enabled',
            {}
        )
        .then((result: any) => {
            const enabled = result.enabled;
            monochrome.value = !enabled;
        })
        .catch((error) => {
            window.console.error(error);
        });
    }

    return {
        monochrome,
        updateLogo,
    };
}