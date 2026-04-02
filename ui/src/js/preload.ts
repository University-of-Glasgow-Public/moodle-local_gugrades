/**
 * Composable to preload aggregation data
 * (where possible)
 */

import { moodleFetch } from '@/js/moodlefetch';
import type { ICategories } from './Interfaces';

export function usePreload() {

    const recalculate = () => {

        // Get all the level 1 categories.
        moodleFetch(
            'local_gugrades_get_levelonecategories',
            {}
        )
        .then((result: any) => {
            const categories: ICategories[] = result.categories;
            const erroritems = result.erroritems;

            // If there are erroritems, no point continuing
            if (erroritems.length != 0) {
                console.log('Errors found in get_levelonecategories. Aborting preload');
                return;
            }

            categories.forEach((cat) => {
                const catid = cat.id;
                const fullname = cat.fullname.toLowerCase();

                // Add only those that contain 'summative'
                // (better than nothing)
                if (fullname.includes('summative')) {

                    // Call full recalculate.
                    moodleFetch(
                        'local_gugrades_recalculate',
                        {
                            gradecategoryid: catid,
                        }
                    )
                    .then(() => {
                        console.log('Recalculated ' + cat.fullname)
                    })
                    .catch(error => {
                        console.error(error);
                    });
                }
            });
        });

    }

    return { recalculate };
}