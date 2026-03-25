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
                            :options="{
                                single: mstrings['recursive_single'],
                                recursive: mstrings['recursive_recursive']
                            }",
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
                        :options="{
                            admin: mstrings['importadditional_admin'],
                            missing: mstrings['importadditional_missing'],
                            update: mstrings['importadditional_update']
                        }"
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
    import {ref, inject, computed} from '@vue/runtime-core';
    import { storeToRefs } from 'pinia';
    import { useToast } from "vue-toastification";
    import TwButton from '../Tailwind/TwButton.vue';
    import PleaseWait from '@/components/PleaseWait.vue';
    import DebugDisplay from '@/components/DebugDisplay.vue';
    import { useMstrings } from '@/stores/mstrings.js';

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
        return props.groupid > 0;
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
    const gradetypes = ref([]);
    const other = ref('');
    const level = ref(0);
    const dryruncount = ref(0);
    const showdryrun = ref(false);
    const loading = ref(false);
    const debug = ref({});
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

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
        const options: Record<string, string> = {
            none: mstrings.value['donotfill'],
            fillns: mstrings.value['fillns'],

        };

        // NS0 only available level >=2
        if (level.value > 1) {
            options.fillns0 = mstrings.value['fillns0'];
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
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        fetchMany([{
            methodname: 'local_gugrades_get_gradetypes',
            args: {
                courseid: courseid,
                gradeitemid: props.itemid,
            }
        }])[0]
        .then((result) => {
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
     function importsingle(is_dryrun) {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        fetchMany([{
            methodname: 'local_gugrades_import_grades_users',
            args: {
                courseid: courseid,
                gradeitemid: props.itemid,
                additional: importadditional.value,
                fillns: importfillns.value,
                userlist: props.userids,
                reason: is_importgrades.value ? reason.value : 'FIRST',
                other: is_importgrades.value ? other.value : '',
                dryrun: is_dryrun,
            }
        }])[0]
        .then((result) => {
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
    function importrecursive(is_dryrun) {
        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        fetchMany([{
            methodname: 'local_gugrades_import_grades_recursive',
            args: {
                courseid: courseid,
                gradeitemid: props.itemid,
                groupid: props.groupid,
                additional: importadditional.value,
                fillns: importfillns.value,
                reason: is_importgrades.value ? reason.value : 'FIRST',
                other: is_importgrades.value ? other.value : '',
                dryrun: is_dryrun,
            }
        }])[0]
        .then((result) => {
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

        const GU = window.GU;
        const courseid = GU.courseid;
        const fetchMany = GU.fetchMany;

        get_gradetypes();

        fetchMany([{
            methodname: 'local_gugrades_is_grades_imported',
            args: {
                courseid: courseid,
                gradeitemid: props.itemid,
                groupid: props.groupid,
            }
        }])[0]
        .then((result) => {
            is_importgrades.value = result.imported;
            recursiveavailable.value = result.recursiveavailable;
            recursivematch.value = result.recursivematch;
            allgradesvalid.value = result.allgradesvalid;
            level.value = result.level;
        })
        .catch((error) => {
            window.console.error(error);
            showimportmodal.value = false;
            debug.value = error;
        });
    }

</script>
