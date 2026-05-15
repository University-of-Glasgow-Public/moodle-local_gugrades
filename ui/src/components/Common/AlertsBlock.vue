<template>
    <div v-if="errors.length" class="mt-2">
        <div class="bg-warning/30 border-warning text-xs px-2 py-1 flex justify-start">
            <div class="w-8 mr-3">&nbsp;</div>
            <div>
                <span class="text-error font-bold">
                    {{ errors.length }}
                    <span v-if="errors.length == 1">warning</span>
                    <span v-else>warnings</span>
                </span>
                may require your attention.
            </div>
        </div>
        <div v-for="error in errors" class="bg-warning/30 border-warning border-t text-xs px-2 py-1 flex justify-start">
            <div class="w-8 mr-3 inline-block align-middle">
                <Circle v-if="error.level == 'error'" :size="18" class="text-error" fill="var(--color-error)"/>
                <Circle v-else class="text-warning" :size="18" fill="var(--color-warning)" />
            </div>
            <div>
                <div class="font-bold">
                    {{ error.warning }}
                </div>
                <div>{{ error.help }}</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
    import type { IError } from '@/js/Interfaces';
    import { Circle } from "@lucide/vue";

    const props = defineProps<{
        errors: IError[]
    }>();
</script>
