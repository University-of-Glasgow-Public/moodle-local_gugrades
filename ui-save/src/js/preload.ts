/**
 * Composable to preload aggregation data
 * (where possible)
 */

import { moodleFetch } from '@/js/moodlefetch';

export function usePreload() {

    const recalculate = () => {

        // Get all the level 1 categories.
        moodleFetch(
            'local_gugrades_background_setup',
            {}
        )
        .then(() => {
            console.log('Background setup complete');
        })
        .catch((error) => {
            console.error(error);
        });
    }

    return { recalculate };
}