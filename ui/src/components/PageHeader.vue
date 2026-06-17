<template>
    <div class="navbar bg-base-100 border-x border-t border-base-300 rounded-t-md flex justify-between mt-4 px-6">
        <div>
            <a :href="courseurl" class="text-sm" ><MoveLeft :size="18" class="inline" /> Back to course</a>
        </div>
        <GreyLogo />
        <RegulationsBadge />
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue';
    import { MoveLeft } from '@lucide/vue';
    import GreyLogo from './Common/GreyLogo.vue';
    import ThemeSelect from './Common/ThemeSelect.vue';
    import RegulationsBadge from './RegulationsBadge.vue';

    /**
     * Work out link back to course page
     *
     */
    const courseurl = computed(() => {

        // Extract the base URL
        const currentUrl = window.location.href;
        const baseUrl = currentUrl.split('/local/gugrades/ui/dist')[0];

        // Extract the courseid from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get('courseid');

        // Create the link to the course view
        if (courseId) {

          return `${baseUrl}/course/view.php?id=${courseId}`;
        } else {
          console.error('No courseid parameter found in the URL.');

          return '';
        }
    });
</script>