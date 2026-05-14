<template>
    <div v-if="errors.length" class="border rounded-md mt-6 border-warning shadow-sm">
        <div class="bg-warning/50 border-warning text-sm p-2">
            <span class="text-error font-bold">{{ errors.length }} warning(s)</span> may require your attention.
        </div>
        <div v-for="error in errors" class="bg-warning/50 border-warning border-t text-sm p-2 flex justify-start">
            <div class="mr-3">
                <Circle v-if="error.level == 'error'" class="text-error" stroke-width="4" />
                <Circle v-else class="text-warning" stroke-width="4" />
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
