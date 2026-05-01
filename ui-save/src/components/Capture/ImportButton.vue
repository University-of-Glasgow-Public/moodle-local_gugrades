<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <TwButton color="primary" @click="import_button_click" :disabled="!enable">
        <span v-if="groupimport">{{ mstrings['importgradesgroup'] }}</span>
        <span v-else>{{ mstrings['importgrades'] }}</span>
    </TwButton>

    <VueModal v-model="showimportmodal" :enableClose="false" modalClass="tw:rounded tw:max-w-3xl" :title="mstrings['importgrades']">

        <div v-if="loading">
            <PleaseWait progresstype="import" :staffuserid="props.staffuserid"></PleaseWait>
        </div>

        <div v-if="showdryrun" class="tw:text-center">

            <p v-if="dryruncount > 0" v-html="mstrings['importdryrun']"></p>
            <p v-else v-html="mstrings['importdryrunzero']"></p>
            <p v-if="dryruncount > 0" class="tw:text-[56px]/17 tw:font-light">{{ dryruncount }}</p>

            <div class="tw:divider"></div>

            <div class="tw:mt-2 tw:pt-2">
                <TwButton v-if="dryruncount > 0" color="primary" @click="importgrades()">{{ mstrings['yesimport'] }}</TwButton>
                <TwButton color="warning" @click="showimportmodal = false">{{ mstrings['cancel'] }}</TwButton>
            </div>
        </div>

        <div v-if="!loading && !showdryrun">

            <!-- already imported warning-->
            <div class="tw:alert tw:alert-soft tw:alert-vertical tw:sm:alert-horizontal tw:mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="tw:stroke-info tw:h-6 tw:w-6 tw:shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div v-if="is_importgrades">
                    {{ mstrings['gradesimported'] }}
                    <p v-if="groupimport"><b>{{ mstrings['importinfogroup'] }}</b></p>
                </div>
                <div v-else>
                    {{ mstrings['importinfo'] }}
                    <p v-if="groupimport"><b>{{ mstrings['importinfogroup'] }}</b></p>
                </div>
                <TwButton color="warning" @click="showimportmodal = false">{{ mstrings['cancel'] }}</TwButton>
            </div>

            <FormKit type="form" :actions="false">

                <!-- Recursive options -->
                <div v-if="recursiveavailable">
                    <div v-if="!allgradesvalid" class="tw:alert tw:alert-danger">
                        {{ mstrings['invalidgradetype'] }}
                    </div>
                    <div v-else>
                        <FormKit
                            type="radio"
                            :label="mstrings['recursiveimport']"
                            :options="recursiveimportoptions",
                            name="recursiveimport"
                            v-model="recursiveselect"
                            >
                        </FormKit>
                    </div>

                    <div class="tw:divider"></div>
                </div>

                <!-- NS fill options -->
                <FormKit
                    type="radio"
                    :label="mstrings['importfillns']"
                    :options="nsoptions"
                    name="importfillns"
                    v-model="importfillns"
                    >
                </FormKit>

                <!-- If there are existing grades then show all the options for importing extra grades -->
                <div v-if="is_importgrades">

                    <div class="tw:divider"></div>

                    <FormKit
                        type="radio"
                        :label="mstrings['importadditional']"
                        name="importadditional"
                        :options="additionaloptions"
                        v-model="importadditional"
                        >
                    </FormKit>
                    <div class="tw:divider"></div>
                    <FormKit
                        type="select"
                        :label="mstrings['reasonforadditionalimport']"
                        name="reason"
                        v-model="reason"
                        :options="gradetypes"
                        :placeholder="mstrings['selectareason']"
                        validation="required"
                    />
                    <FormKit
                        v-if = 'reason == "OTHER"'
                        :label="mstrings['pleasespecify']"
                        type="text"
                        :placeholder="mstrings['pleasespecify']"
                        name="other"
                        v-model="other"
                    />
                </div>
            </FormKit>

            <div v-if="recursiveavailable && (recursiveselect=='recursive') && !recursivematch" class="tw:mt-2 tw:alert tw:alert-warning">
                {{ mstrings['importnomatch'] }}
            </div>

            <div class="tw:divider"></div>

            <div class="tw:mt-2 tw:pt-2">
                <TwButton color="primary" @click="dryrungrades()">{{ mstrings['yesimport'] }}</TwButton>
                <TwButton color="warning" @click="showimportmodal = false">{{ mstrings['cancel'] }}</TwButton>
            </div>
        </div>
    </VueModal>
</template>

<script setup lang="ts">
    import {ref, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import TwButton from '../Tailwind/TwButton.vue';
    import PleaseWait from '@/components/Common/PleaseWait.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';
    import type { IGradetype, IFormkitOption } from '@/js/Interfaces';

    interface IFormkitOptions {
        label: string;
        value: string;
    }

    const props = defineProps({
        enable: {
            type: Boolean,
            default: false,
        },
        userids: Array,
        itemid: Number,
        groupid: Number,
        show: Boolean,
        staffuserid: Number,
    });

    const toast = useToast();

    const groupimport = computed(() => {
        return props.groupid ? props.groupid > 0 : false;
    });

    const emit = defineEmits(['imported']);

    const showimportmodal = ref(false);
    const is_importgrades = ref(false);
    const recursiveavailable = ref(false);
    const recursivematch = ref(false);
    const recursiveselect = ref<'single' | 'recursive'>('single');
    const reason = ref('SECOND');
    const importadditional = ref<'admin' | 'missing' | 'update'>('admin');
    const importfillns = ref<'none' | 'fillns'>('none');
    const allgradesvalid = ref(false);
    const gradetypes = ref< IGradetype[] >([]);
    const other = ref('');
    const level = ref(0);
    const dryruncount = ref(0);
    const showdryrun = ref(false);
    const ns0used = ref(false);
    const loading = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    // Options for radio "importadditional"
    const additionaloptions: IFormkitOption[] = [
        {value: 'admin', label: mstringstore.getMstring('importadditional_admin')},
        {value: 'missing', label:  mstringstore.getMstring('importadditional_missing')},
        {value: 'update', label: mstringstore.getMstring('importadditional_update')}
    ];

    // Options for "recursiveimport".
    const recursiveimportoptions: IFormkitOption[] = [
        {value: 'single', label: mstringstore.getMstring('recursive_single')},
        {value: 'recursive', label: mstringstore.getMstring('recursive_recursive')}
    ];

    /**
     * What kind of alert do you get?
     */
    const importclass = computed(() => ({
        'tw:alert-error' : is_importgrades.value,
        'tw:alert-info' : !is_importgrades.value,
    }));

    /**
     * Options for NS/NS0 dropdown
     */
    const nsoptions = computed(() => {

        const options: IFormkitOption[] = [
            {value: 'none', label: mstringstore.getMstring('donotfill')},
            {value: 'fillns', label: mstringstore.getMstring('fillns')}

        ];

        // NS0 only available level >=2 (if permitted by regulations)
        if ((level.value > 1) && ns0used.value) {
            options.push(
                {value: 'fillns0', label: mstringstore.getMstring('fillns0')}
            );
        }

        return options;
    });

    /**
     * Do dry run. Select appropriate import function
     */
    function dryrungrades() {

        loading.value = true;
        dryruncount.value = 0;

        if (recursiveselect.value == 'recursive') {
            importrecursive(true);
        } else {
            importsingle(true);
        }
    }

    /**
     * Do proper import. Select appropriate import function
     */
    function importgrades() {

        loading.value = true;

        if (recursiveselect.value == 'recursive') {
            importrecursive(false);
        } else {
            importsingle(false);
        }
    }

    /**
     * Get the add grade form stuff
     */
    function get_gradetypes() {
        moodleFetch(
            'local_gugrades_get_gradetypes',
            {
                gradeitemid: props.itemid,
            }
        )
        .then((result: any) => {
            gradetypes.value = result.gradetypes;
        })
        .catch((error) => {
            window.console.error(error);
            showimportmodal.value = false;
            debug.value = error;
        });
    }

    /**
     * Import single grade item
     */
     function importsingle(is_dryrun: boolean) {
        moodleFetch(
            'local_gugrades_import_grades_users',
            {
                gradeitemid: props.itemid,
                additional: importadditional.value,
                fillns: importfillns.value,
                userlist: props.userids,
                reason: is_importgrades.value ? reason.value : 'FIRST',
                other: is_importgrades.value ? other.value : '',
                dryrun: is_dryrun,
            }
        )
        .then((result: any) => {
            const importcount = result['importcount'];
            dryruncount.value = importcount;
            loading.value = false;

            // Only close the modal after we've shown the dry run count.
            if (showdryrun.value) {
                emit('imported');
                if (dryruncount) {
                    toast.success(mstrings.value['gradesimportedsuccess']);
                } else {
                    toast.warning(mstrings.value['nogradestoimport']);
                }

                showimportmodal.value = false;
            } else {
                showdryrun.value = true;
            }
        })
        .catch((error) => {
            showimportmodal.value = false;
            debug.value = error;
            window.console.error(error);
        });
    }

    /**
     * Import recursive grades
     */
    function importrecursive(is_dryrun: boolean) {
        moodleFetch(
            'local_gugrades_import_grades_recursive',
            {
                gradeitemid: props.itemid,
                groupid: props.groupid,
                additional: importadditional.value,
                fillns: importfillns.value,
                reason: is_importgrades.value ? reason.value : 'FIRST',
                other: is_importgrades.value ? other.value : '',
                dryrun: is_dryrun,
            }
        )
        .then((result: any) => {
            const itemcount = result.itemcount;
            const gradecount = result.gradecount;
            dryruncount.value = gradecount;
            loading.value = false;

            // Only close the modal after we've shown the dry run count.
            if (showdryrun.value) {
                emit('imported');
                if (dryruncount) {
                    toast.success(mstrings.value['gradesimportedsuccess']);
                } else {
                    toast.warning(mstrings.value['nogradestoimport']);
                }

                showimportmodal.value = false;
            }
            else {
                showdryrun.value = true;
            }
        })
        .catch((error) => {
            showimportmodal.value = false;
            debug.value = error;
            window.console.error(error);
        });
    }

    /**
     * When button clicked
     * Check for existing grades
     */
    function import_button_click() {
        showimportmodal.value = true;
        importadditional.value = 'admin';
        recursiveselect.value = 'single';
        importfillns.value = 'none';
        reason.value='SECOND';
        other.value='';
        dryruncount.value = 0;
        showdryrun.value = false;
        loading.value = false;

        get_gradetypes();

        moodleFetch(
            'local_gugrades_is_grades_imported',
            {
                gradeitemid: props.itemid,
                groupid: props.groupid,
            }
        )
        .then((result: any) => {
            is_importgrades.value = result.imported;
            recursiveavailable.value = result.recursiveavailable;
            recursivematch.value = result.recursivematch;
            allgradesvalid.value = result.allgradesvalid;
            level.value = result.level;
            ns0used.value = result.ns0used;
        })
        .catch((error) => {
            window.console.error(error);
            showimportmodal.value = false;
            debug.value = error;
        });
    }

</script>
