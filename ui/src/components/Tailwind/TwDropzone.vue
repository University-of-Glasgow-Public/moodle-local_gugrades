<template>
    <div ref="dropZoneRef" class="tw:flex tw:items-center tw:justify-center tw:w-full tw:mb-4" @click="() => open">
        <div  class="tw:flex tw:flex-col tw:items-center tw:justify-center tw:w-full tw:h-64 tw:bg-neutral-secondary-medium tw:border tw:border-dashed tw:border-default-strong tw:rounded-base tw:cursor-pointer tw:hover:bg-neutral-tertiary-medium">
            <div class="tw:flex tw:flex-col tw:items-center tw:justify-center tw:text-body tw:pt-5 tw:pb-6">
                <svg class="tw:w-8 tw:h-8 tw:mb-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h3a3 3 0 0 0 0-6h-.025a5.56 5.56 0 0 0 .025-.5A5.5 5.5 0 0 0 7.207 9.021C7.137 9.017 7.071 9 7 9a4 4 0 1 0 0 8h2.167M12 19v-9m0 0-2 2m2-2 2 2"/></svg>
                <p class="tw:mb-2 tw:text-sm"><span class="tw:font-semibold">Click to upload</span> or Drop file here</p>
                <slot></slot>
                <p v-if="filename">{{ filename }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang='ts'>
    import { useDropZone, useFileDialog } from '@vueuse/core';
    import { useTemplateRef, ref } from 'vue'

    const dropZoneRef = useTemplateRef('dropZoneRef');

    const filename = ref('');

    const props = defineProps<{
        mimetypes: string[],
        accept: string,
    }>();

    const emits = defineEmits(['onchange']);

    const { isOverDropZone } = useDropZone(dropZoneRef, {
        onDrop,
        // specify the types of data to be received.
        dataTypes: props.mimetypes,
        // control multi-file drop
        multiple: false,
        // whether to prevent default behavior for unhandled events
        preventDefaultForUnhandled: false,
    });

    const { files, open, reset, onCancel, onChange } = useFileDialog({
        accept: props.accept,
    });

    function onDrop(files: File[] | null, event?: DragEvent) {
        if (files && files[0]) {
            filename.value = files[0].name;
            emits("onchange", files[0]);
        }
    }

    onChange((files) => {
        if (files) {
            onDrop(Array.from(files));
        }
    });
</script>