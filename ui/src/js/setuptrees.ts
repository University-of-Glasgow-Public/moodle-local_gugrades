/**
 * Composable to populate the trees of grade categories
 * and items when MyGrades loads. Done as this process
 * can be slow.
 */

import { useActivityTreeStore } from '../stores/activitytree.js';
import { moodleFetch } from '@/js/moodlefetch';
import type { ICategories } from './Interfaces.js';

export function usePopulateTrees() {

    const populate = () => {

        // Get all the level 1 categories.
        return moodleFetch(
            'local_gugrades_get_levelonecategories',
            {}
        )
        .then((result: any) => {
            const activitytree = useActivityTreeStore();
            let promises: Promise<any>[] = [];
            const categories: ICategories[] = result.categories;
            categories.forEach(cat => {
                const catid = cat.id;
                promises.push(

                    // Get the (detailed) tree for this top level category.
                    moodleFetch(
                        'local_gugrades_get_activities',
                        {
                            categoryid: catid,
                            detailed: true,
                        }
                    )
                    .then((result:any) => {
                        activitytree.trees[catid] = result.activities;
                        activitytree.errors[catid] = result.error;
                    })
                    .catch(error => {
                        console.error(error);
                    })
                );
            });

            return Promise.all(promises).then(() => {
                activitytree.ready = true;
                console.log('Activity trees preloaded');
            });
        })
        .catch(error => {
            console.error(error);
        });
    }

    return { populate };
}