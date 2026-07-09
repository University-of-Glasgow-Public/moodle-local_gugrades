<template>
  <TourGuideManager
    ref="tourManager"
    :steps="toursteps"
    :auto-start="false" 
    @complete="onTourComplete"
    @skip="onTourComplete"
  />
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { TourGuideManager, type TourGuideStep } from 'v-tour-guide';
    import { moodleFetch } from '@/js/moodlefetch.ts';
    import YAML from 'yaml';
    import 'v-tour-guide/style.css';

    const tourManager = ref<InstanceType<typeof TourGuideManager> | null>(null);
    const toursteps = ref<TourGuideStep[]>([]);

    const tooltip = {
        backgroundColor: 'var(--color-brand-light-purple)',
        textColor: 'var(--color-brand-dark-purple)',
        buttonBackgroundColor: 'var(--color-university-blue)',
        buttonTextColor: '#ffffff',
        borderRadius: '0.75rem', // Match rounded-xl
        padding: '1.25rem',      // Match p-5
        boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)' // Match shadow-2xl
    };

    onMounted(() => {
        moodleFetch('local_gugrades_get_usertour', {})
        .then((result: any) => {
            const active = result.active ?? false;

            if (active) {
                const yaml = result.yaml;
                const steps = YAML.parse(yaml);
                toursteps.value = steps.map((step: TourGuideStep) => {
                    step['tooltip'] = tooltip;
                    step['showAction'] = true;
                    return step;
                });

                // Use a short delay to ensure the DOM is ready
                setTimeout(() => {
                    tourManager.value?.startTourGuide();
                }, 500); 
            }
        })
        .catch((error) => {
            console.error(error);
        });

    });

    const onTourComplete = () => {
        moodleFetch('local_gugrades_set_tour_state', {enabled: false})
        .catch((error) => {
            console.error(error);
        });
    };
</script>
