<template>
    <UTooltip v-if="props.shortnote" :text="props.shortnote">
        <UButton variant="warning" size="xs" @click.stop.prevent="opennote"><NotepadText :size="12" /> Note</UButton>
    </UTooltip>
    <div v-else>
        <UButton appearance="outline" size="xs" @click.stop.prevent="opennote"><Plus :size="12" /> Note</UButton>
    </div>

    <HeadlessModal :isopen="noteopen" @closed="closenote">
        <template #title>
            Note: {{ props.name }}
        </template>

        <textarea v-model="note" class="w-full min-h-32 p-2 border border-gray-200 rounded"></textarea>

    </HeadlessModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { Plus, NotepadText } from '@lucide/vue';
    import HeadlessModal from '../Tailwind/HeadlessModal.vue';
    import UButton from './UButton.vue';
    import UTooltip from './UTooltip.vue';
    import { moodleFetch } from '@/js/moodlefetch';

    const props = defineProps({
        userid: {
            type: Number,
            required: true,
        },
        gradeitemid: {
            type: Number,
            required: true,
        },
        name: {
            type: String,
            required: true,
        },
        shortnote: {
            type: String,
            default: '',
        }
    });

    const emits = defineEmits(['updated']);

    const noteopen = ref(false);
    const note = ref('');
    const editorReady = ref(false);

    function opennote() {
        moodleFetch('local_gugrades_read_note', {
            userid: props.userid,
            gradeitemid: props.gradeitemid,
        })
        .then((result: any) => {
            note.value = result.note;
            noteopen.value = true;
            setTimeout(() => editorReady.value = true, 200);
        })
        .catch((error) => {
            console.error(error);
        })
    }

    function closenote() {
        editorReady.value = false;
        noteopen.value = false;

        moodleFetch('local_gugrades_write_note', {
            userid: props.userid,
            gradeitemid: props.gradeitemid,
            note: note.value,
        })
        .then(() => {
            emits('updated');
        })
        .catch((error) => {
            console.error(error);
        });

    }
</script>