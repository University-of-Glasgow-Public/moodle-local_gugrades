<template>
    <div
        ref="dropZoneRef"
        class="flex items-center justify-center w-full mb-4"
        @click="handleClick"
        @dragover.prevent="handleDragOver"
        @dragenter.prevent="handleDragEnter"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDropEvent"
    >
        <div
            class="flex flex-col items-center justify-center w-full h-64 bg-neutral-secondary-medium border-2 border-dashed border-gray-400 rounded-base cursor-pointer hover:bg-neutral-tertiary-medium"
            :class="{ 'border-primary border-2': isOverDropZone }"
        >
            <div class="flex flex-col items-center justify-center text-body pt-5 pb-6">
                <svg class="w-8 h-8 mb-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h3a3 3 0 0 0 0-6h-.025a5.56 5.56 0 0 0 .025-.5A5.5 5.5 0 0 0 7.207 9.021C7.137 9.017 7.071 9 7 9a4 4 0 1 0 0 8h2.167M12 19v-9m0 0-2 2m2-2 2 2"/>
                </svg>
                <p class="mb-2 text-sm">
                    <span class="font-semibold">Click to upload</span> or Drop file here
                </p>
                <slot></slot>
                <p v-if="filename">{{ filename }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useDropZone, useFileDialog } from '@vueuse/core';

const dropZoneRef = ref<HTMLElement | null>(null);
const filename = ref('');
const isOverDropZone = ref(false);

const props = defineProps<{
    mimetypes: string[];
    accept: string;
}>();

const emits = defineEmits(['onchange']);

const { files, open: openFileDialog, reset, onChange } = useFileDialog({
    accept: props.accept,
});

const { isOverDropZone: isOverDropZoneInternal } = useDropZone(dropZoneRef, {
    onDrop,
});

watch(isOverDropZoneInternal, (val: boolean) => {
    isOverDropZone.value = val;
});

function handleClick() {
    openFileDialog();
}

function handleDragOver(event: DragEvent) {
    event.preventDefault();
    isOverDropZone.value = true;
}

function handleDragEnter(event: DragEvent) {
    event.preventDefault();
    isOverDropZone.value = true;
}

function handleDragLeave() {
    isOverDropZone.value = false;
}

function handleDropEvent(event: DragEvent) {
    event.preventDefault();
    isOverDropZone.value = false;
}

function handleDrop(files: File[] | null) {
    if (files && files[0]) {
        filename.value = files[0].name;
        emits('onchange', files[0]);
    }
}

function onDrop(files: File[] | null) {
    handleDrop(files);
}

onChange((files) => {
    if (files && files[0]) {
        filename.value = files[0].name;
        emits('onchange', files[0]);
    }
});
</script>