<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <button @click="recalculate_clicked" class="btn btn-outline btn-secondary mr-2 btn-sm">
        <Sigma :size="18" />
        {{ mstrings.recalculate }}
    </button>

    <VueModal v-model="showrecalculatemodal" :enableClose="false" modalClass="rounded max-w-3xl" :title="mstrings.recalculate">
        <div v-if="loading">
            <PleaseWait :staffuserid="props.staffuserid" progresstype="aggregate"></PleaseWait>
        </div>

        <div v-else>
            <TwAlert class="mb-5">{{ mstrings.recalculatehelp }}</TwAlert>

            <TwButton color="primary" class="mr-1"  @click="do_recalculate()">{{  mstrings.recalculate }}</TwButton>
            <TwButton color="warning" @click="showrecalculatemodal = false">{{  mstrings.cancel }}</TwButton>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import TwAlert from '../Tailwind/TwAlert.vue';
    import TwButton from '../Tailwind/TwButton.vue';
    import { Sigma } from '@lucide/vue'

    const showrecalculatemodal = ref(false);
    const loading = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const props = defineProps({
        categoryid: Number,
        staffuserid: Number,
    });

    const emits = defineEmits([
        'recalculated'
    ]);

    const toast = useToast();

    /**
     * Recalculate button has been clicked
     */
    function recalculate_clicked() {
        showrecalculatemodal.value = true;
    }

    /**
     * Perform recalculation
     */
    function do_recalculate() {

        loading.value = true;

        moodleFetch(
            'local_gugrades_recalculate',
            {
                gradecategoryid: props.categoryid,
            }
        )
        .then(() => {
            toast.success('Grades recalculated');
            loading.value = false;
            showrecalculatemodal.value = false;
            emits('recalculated');
        })
        .catch((error) => {
            window.console.error(error);
            showrecalculatemodal.value = false;
            debug.value = error;
        });
    }
</script>