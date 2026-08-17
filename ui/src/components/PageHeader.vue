<template>
    <div class="w-full mt-4 grid grid-cols-3 items-center justify-between border-x border-t border-brand-light-purple/30 bg-white rounded-t-xl px-6 py-3 text-brand-dark-purple shadow-sm">
        
        <div class="flex items-start gap-3 max-w-xs md:max-w-sm">
            <a :href="url" class="text-university-blue hover:text-brand-dark-blue transition-colors pt-0.5 shrink-0" aria-label="Go back">
                <MoveLeft :size="18" />
            </a>

            <div class="flex flex-col min-w-0 space-y-1">
                <a :href="url" class="text-sm font-semibold text-university-blue hover:underline truncate block" :title="fullname">
                    Back to {{ fullname }}
                </a>
                
                <p class="text-xs text-brand-dark-purple/60 font-medium">
                    Start date: <span class="font-mono text-brand-dark-purple/80">{{ startdate }}</span>
                </p>
                
                <div class="pt-0.5">
                    <span class="inline-block text-[11px] font-bold tracking-wider uppercase bg-brand-dark-pink/10 text-brand-dark-pink px-2 py-0.5 rounded border border-brand-dark-pink/20 shadow-inner">
                        {{ specialcategory }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex justify-center shrink-0">
            <GreyLogo />
        </div>

        <!-- empty block ensures the layout math centers the middle logo -->
        <div class="hidden md:block"></div>

    </div>
</template>

<script setup lang="ts">
    import { computed, onMounted, ref } from 'vue';
    import { moodleFetch } from '@/js/moodlefetch.ts';
    import { MoveLeft } from '@lucide/vue';
    import GreyLogo from './Common/GreyLogo.vue';

    const fullname = ref('');
    const url = ref('');
    const startdate = ref('');
    const specialcategory = ref('');

    onMounted(() => {
        moodleFetch('local_gugrades_get_course_info', {})
        .then((result: any) => {
            fullname.value = result.fullname;
            url.value = result.url;
            startdate.value = result.startdate;
            specialcategory.value = result.specialcategory;
        })
        .catch((error) => {
            console.error(error);
        });
    })
</script>