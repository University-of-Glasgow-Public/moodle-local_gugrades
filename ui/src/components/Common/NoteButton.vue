<template>
    <div v-if="props.shortnote" class="tooltip" :data-tip="props.shortnote">
        <button class="btn btn-warning btn-xs" @click.prevent="opennote"><NotepadText :size="12" /> Note</button>
    </div>
    <div v-else>
        <button class="btn btn-xs btn-dash" @click.prevent="opennote"><Plus :size="12" /> Note</button>
    </div>

    <HeadlessModal :isopen="noteopen" @closed="closenote">
        <template #title>
            {{ props.name }}
        </template>

        <TipTap v-model="note"></TipTap>

    </HeadlessModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { Plus, NotepadText } from '@lucide/vue';
    import HeadlessModal from '../Tailwind/HeadlessModal.vue';
    import TipTap from './TipTap.vue';
    import { moodleFetch } from '@/js/moodlefetch';

    const props = defineProps({
        userid: {
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

    function opennote() {
        moodleFetch('local_gugrades_read_note', {
            userid: props.userid,
        })
        .then((result: any) => {
            note.value = result.note;
        })
        .catch((error) => {
            console.error(error);
        });

        noteopen.value = true;
    }

    function closenote() {
        moodleFetch('local_gugrades_write_note', {
            userid: props.userid,
            note: note.value,
        })
        .then(() => {
            emits('updated');
        })
        .catch((error) => {
            console.error(error);
        });

        noteopen.value = false;
    }
</script>