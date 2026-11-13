<template>
    <DebugDisplay :debug="debug"></DebugDisplay>
    <ConfirmModal :show="showundoconfirm" :message="undomessage" @confirm="confirm_undo" />

    <a  @click.prefent="add_grade()" class="block px-3 py-2.5 text-sm cursor-pointer transition-colors hover:bg-university-blue/10">
        {{ buttontitle }}
    </a>

    <!-- Upgraded modal classes for better shadows and borders -->
    <VueModal v-model="showaddgrademodal" :enableClose="false" modalClass="rounded-xl max-w-3xl border border-brand-light-purple/30 bg-white shadow-xl" :title="buttontitle">

        <!-- Main Form Section -->
        <div v-if="!showreleaseddialogue" class="p-6 text-brand-dark-purple">
            
            <div class="mb-6 p-4 rounded-lg bg-brand-light-purple/10 border border-brand-light-purple/30 shadow-inner">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    
                    <!-- Left Column: Primary Context Details -->
                    <div class="space-y-2">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold uppercase tracking-wider text-brand-dark-purple/60">
                                {{ props.categoryid ? mstrings.category : mstrings.itemname }}
                            </span>
                            <span class="font-bold text-base text-university-blue">
                                {{ itemname }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs font-bold uppercase tracking-wider text-brand-dark-purple/60">
                                {{ mstrings.username }}
                            </span>
                            <span class="font-medium text-brand-dark-purple">
                                {{ name }}
                            </span>
                        </div>
                    </div>

                    <!-- Right Column: Identifiers & Dynamic Branded Badges -->
                    <div class="space-y-2 flex flex-col md:items-end justify-between">
                        <div class="flex flex-col md:items-end">
                            <span class="text-xs font-bold uppercase tracking-wider text-brand-dark-purple/60">
                                {{ mstrings.idnumber }}
                            </span>
                            <code class="font-mono text-xs bg-university-blue/10 px-2 py-0.5 rounded text-university-blue font-bold">
                                {{ idnumber }}
                            </code>
                        </div>

                        <!-- Pill badges using your exact light palette settings -->
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span v-if="overridden" class="inline-block text-xs font-bold bg-brand-light-yellow text-brand-dark-purple px-2 py-0.5 rounded-md shadow-sm border border-brand-light-yellow/80">
                                ⚠ {{ mstrings.categoryoverridden }}
                            </span>
                            <span v-if="props.released" class="inline-block text-xs font-bold bg-brand-light-green text-brand-dark-purple px-2 py-0.5 rounded-md shadow-sm border border-brand-light-green/80">
                                ✓ {{ mstrings.releasedgrade }}
                            </span>
                        </div>
                    </div>

                </div>

                <!-- Sub-text label string if present -->
                <div v-if="reason" class="mt-3 pt-3 border-t border-brand-light-purple/20 text-xs italic text-brand-dark-purple/70">
                    {{ reason }}
                </div>
            </div>

            <!-- Message Alerts Windows Layout Adjustments -->
            <UAlert v-if="!available" class="mb-4 bg-brand-light-yellow/10 border border-brand-light-yellow/50 rounded-lg p-3 text-brand-dark-purple">
                {{ mstrings.overridenotavailable }}
            </UAlert>

            <UAlert variant="error" v-if="error" class="mb-4 bg-brand-dark-red/10 border border-brand-dark-red/50 rounded-lg p-3 text-brand-dark-red">
                {{ mstrings.overrideerror }}
            </UAlert>

            <!-- FORM WRAPPER: Enhanced padding and clean borders -->
            <FormKit v-if="!overridden && available && !error" class="bg-white p-5 border border-brand-light-purple/30 rounded-xl shadow-sm" type="form" @submit="submit_form">
                <FormKit
                    v-if="!iscategory"
                    type="select"
                    outer-class="mb-3"
                    :label="mstrings.reasonforadditionalgrade"
                    name="reason"
                    v-model="reason"
                    :options="gradetypes"
                    :placeholder="mstrings.selectareason"
                    :validation-messages="{ required: 'This field is required.' }"
                    validation="required"
                    validation-visibility="live"
                />
                <FormKit
                    v-if='reason == "OTHER"'
                    :label="mstrings.pleasespecify"
                    type="text"
                    outer-class="mb-3"
                    :placeholder="mstrings.pleasespecify"
                    name="other"
                    v-model="other"
                    validation="required"
                    :validation-messages="{ required: 'This field is required.' }"
                />
                <FormKit
                    type="select"
                    :label="mstrings.admingrades"
                    name="admingrades"
                    outer-class="mb-3"
                    v-model="admingrade"
                    :options="adminmenu"
                ></FormKit>
                <FormKit
                    v-if="usescale"
                    type="select"
                    outer-class="mb-3"
                    :label="mstrings.grade"
                    :placeholder="mstrings.specifyscale"
                    :disabled="admingrade != 'GRADE'"
                    name="scale"
                    v-model="scale"
                    :options="scalemenu"
                ></FormKit>
                <FormKit
                    v-if="!usescale"
                    type="text"
                    outer-class="mb-3"
                    :label="mstrings.grade"
                    :placeholder="mstrings.specifygrade"
                    :validation="gradevalidation"
                    :disabled="admingrade != 'GRADE'"
                    number="float"
                    validation-visibility="live"
                    name="grade"
                    v-model="grade"
                ></FormKit>
                <FormKit
                    type="textarea"
                    outer-class="mb-3"
                    :label="mstrings.explanation"
                    :placeholder="mstrings.reasonforammendment"
                    name="notes"
                    v-model="notes"
                    validation="required:trim"
                    rows="2"
                />
            </FormKit>

            <!-- Action panel for overridden states -->
            <div v-if="overridden" class="my-4 p-4 bg-brand-light-purple/10 border border-brand-light-purple/30 rounded-lg">
                <UAlert variant="info" class="mb-3 bg-white p-2 border border-brand-light-purple/20 text-brand-dark-purple rounded">{{ mstrings.categoryremoveoverride }}</UAlert>
                <UButton variant="primary" class="bg-brand-dark-purple hover:bg-brand-dark-purple/90 text-white font-medium px-4 py-2 rounded-lg shadow-sm" @click="removeoverride">
                    {{ mstrings.remove }}
                </UButton>
            </div>

            <section v-if="undoid" class="mt-6 border-t-2 border-brand-light-purple/30 pt-5">
                <h3 class="mb-3 text-base font-semibold text-brand-dark-purple">{{ mstrings.undograde }}</h3>
                <FormKit type="form" :actions="false" @submit="showundoconfirm = true">
                    <FormKit
                        type="textarea"
                        :label="mstrings.explanation"
                        name="undoexplanation"
                        v-model="undoexplanation"
                        validation="required:trim"
                        rows="2"
                    />
                    <UButton type="submit" variant="warning" :disabled="undoing">{{ mstrings.undograde }}</UButton>
                </FormKit>
            </section>

            <!-- Main modal actions alignment footer -->
            <div class="flex justify-end mt-6 pt-4 border-t border-brand-light-purple/20">
                <UButton variant="warning" class="bg-brand-dark-red hover:bg-brand-dark-red/90 text-white font-medium px-4 py-2 rounded-lg shadow-sm" @click="closemodal">
                    {{ mstrings.cancel }}
                </UButton>
            </div>
        </div>

        <!-- Re-release Interception Layer Window -->
        <div v-if="showreleaseddialogue" class="p-6 text-brand-dark-purple">
            <UAlert variant="warning" class="bg-brand-light-yellow/20 border-l-4 border-brand-light-yellow rounded-r-lg p-3 text-brand-dark-purple font-medium mb-4">
                {{ mstrings.releasefromadd }}
            </UAlert>
            <div class="mt-4 flex gap-2 justify-end">
                <UButton variant="success" class="bg-brand-dark-green hover:bg-brand-dark-green/90 text-white font-medium px-4 py-2 rounded-lg shadow-sm" @click="release_grade">
                    {{ mstrings.yes }}
                </UButton>
                <UButton variant="warning" type="button" class="border border-brand-dark-red text-brand-dark-red hover:bg-brand-dark-red/5 px-4 py-2 rounded-lg" @click="closemodal">
                    {{ mstrings.no }}
                </UButton>
            </div>
        </div>
    </VueModal>
</template>


<script setup lang="ts">
    import {ref, computed, inject, onBeforeUnmount, watch} from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import { useToast } from "vue-toastification";
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import UButton from '../Common/UButton.vue';
    import UAlert from '../Common/UAlert.vue';
    import ConfirmModal from '../Common/ConfirmModal.vue';
    import type { IGradetype } from '@/js/Interfaces';
    import { gradeDialogSelectionKey } from '@/js/gradeDialogSelection';

    interface IAdminMenu {
        value: string;
        label: string;
    }

    interface IScaleOption {
        value: string;
        label: string;
    }

    type FormKitRule = [string, ...any[]];
    type FormKitValidationRules = FormKitRule[];

    const showaddgrademodal = ref(false);
    const showreleaseddialogue = ref(false);
    const showundoconfirm = ref(false);
    const undoing = ref(false);
    const undoid = ref(0);
    const undograde = ref('');
    const undoreason = ref('');
    const undotime = ref('');
    const undoprevious = ref('');
    const undoexplanation = ref('');
    const debug = ref({});
    const gradetypes = ref< IGradetype[] >([]);
    const idnumber = ref('');
    const reason = ref('');
    const admingrade = ref('GRADE'); // GRADE == not an admin grade (a real grade)
    const scale = ref< string >('');
    const grade = ref(0);
    const notes = ref('');
    const other = ref('');
    const usescale = ref(false);
    const iscategory = ref(false);
    const overridden = ref(false);
    const available = ref(true);
    const error = ref(false);
    const grademax = ref(0);
    const scalemenu = ref< IScaleOption[] >([]);
    const adminmenu = ref< IAdminMenu[] >([]);
    const gradevalidation = ref< FormKitValidationRules >([]);
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs( mstringstore );

    const emit = defineEmits([
        'gradeadded'
    ]);

    const toast = useToast();

    const props = defineProps({
        userid: Number,
        itemid: Number,
        selectedcategoryid: Number,
        categoryid: Number,
        itemname: String,
        name: String,
        released: Boolean,
        close: Function,
    });

    const undomessage = computed(() => {
        let message = (mstrings.value.undogradedetails ?? '')
            .replace('{$a->reason}', undoreason.value)
            .replace('{$a->grade}', undograde.value)
            .replace('{$a->time}', undotime.value)
            .replace('{$a->student}', props.name ?? '')
            .replace('{$a->item}', props.itemname ?? '');
        if (undoprevious.value) {
            message += ' ' + (mstrings.value.undorestore ?? '').replace('{$a}', undoprevious.value);
        }
        return message;
    });

    function confirm_undo(confirmed: boolean) {
        showundoconfirm.value = false;
        if (!confirmed || undoing.value) {
            return;
        }
        undoing.value = true;
        moodleFetch('local_gugrades_undo_additional_grade', {
            gradeitemid: props.itemid,
            userid: props.userid,
            gradeid: undoid.value,
            explanation: undoexplanation.value,
        }).then(() => {
            toast.success(mstringstore.getMstring('undogradedone'));
            grade_added();
            closemodal();
        }).catch((error) => {
            debug.value = error;
            closemodal();
        }).finally(() => {
            undoing.value = false;
        });
    }

    const activeGradeUser = inject(gradeDialogSelectionKey, null);
    const selectionOwner = Symbol('gradeDialog');

    watch([showaddgrademodal, () => props.userid], ([open, userid]) => {
        if (!activeGradeUser) {
            return;
        }
        if (open && userid !== undefined) {
            activeGradeUser.value = { userid, owner: selectionOwner };
        } else if (activeGradeUser.value?.owner === selectionOwner) {
            activeGradeUser.value = null;
        }
    }, { flush: 'sync' });

    onBeforeUnmount(() => {
        if (activeGradeUser?.value?.owner === selectionOwner) {
            activeGradeUser.value = null;
        }
    });

    /**
     * Close modal
     */
    function closemodal() {
        showaddgrademodal.value = false;
        if (props.close) {
            props.close();
        }
    }

    /**
     * Grade has been changed.
     */
    function grade_added() {
        emit('gradeadded');
    }

    /**
     * The title can be for grade or category
     */
    const buttontitle = computed(() => {
        if (props.categoryid) {
            return mstringstore.getMstring('overridecategory');
        } else {
            return mstringstore.getMstring('addgrade');
        }
    });

    /**
     * Get data for form
     */
    function add_grade() {
        notes.value = '';
        undoexplanation.value = '';
        undoid.value = 0;

        moodleFetch(
            'local_gugrades_get_add_grade_form',
            {
                gradeitemid: props.itemid,
                userid: props.userid,
            }
        )
        .then((result: any) => {
            gradetypes.value = result.gradetypes;
            idnumber.value = result.idnumber;
            usescale.value = result.usescale;
            iscategory.value = result.iscategory;
            overridden.value = result.overridden;
            available.value = result.available;
            error.value = result.error;
            grademax.value = result.grademax;
            scalemenu.value = result.scalemenu;
            adminmenu.value = result.adminmenu;
            undoid.value = result.undoid;
            undograde.value = result.undograde;
            undoreason.value = result.undoreason;
            undotime.value = result.undotime;
            undoprevious.value = result.undoprevious;

            // Add 'use grade' option onto front of adminmenu
            adminmenu.value.unshift({
                value: 'GRADE',
                label: mstringstore.getMstring('selectnormalgrade'),
            });

            gradevalidation.value = [
                ['required'],
                ['number'],
                ['between', 0, result['grademax']],
            ];
        })
        .catch((error) => {
            console.error(error);
            debug.value = error;
            closemodal();
        });

        showaddgrademodal.value = true;
    }

    /**
     * Request recalculate single user.
     * Note: no need to wait for response
     */
    function recalculate_user() {
        moodleFetch(
            'local_gugrades_recalculate',
            {
                gradecategoryid: props.selectedcategoryid,
                userid: props.userid,
            }
        )
        .catch(error => {
            console.error(error);
        });
    }

    /**
     * Process form submission
     */
    function submit_form() {

        // We don't ask for the reason if a category. So...
        if (iscategory.value) {
            reason.value = 'CATEGORY';
        }

        moodleFetch(
            'local_gugrades_write_additional_grade',
            {
                gradeitemid: props.itemid,
                userid: props.userid,
                admingrade: admingrade.value == 'GRADE' ? '' : admingrade.value,
                reason: reason.value,
                other: other.value,
                scale:scale.value ? scale.value : 0,
                grade: grade.value,
                notes: notes.value,
                manualentry: true,
            }
        )
        .then(() => {
            grade_added();
            toast.success(mstringstore.getMstring('gradeadded'));

            // If the grade was released then we have more stuff to do
            if (props.released) {
                showreleaseddialogue.value = true;
            } else {
                closemodal();
            }

            recalculate_user();
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
            closemodal();
        });
    }


    /**
     * Grade gets re-released after add
     */
    function release_grade() {

        moodleFetch(
            'local_gugrades_release_grade',
            {
                gradeitemid: props.itemid,
                userid: props.userid,
            }
        )
        .then(() => {

            // This will be sufficient to re-aggregate and so on.
            grade_added();
            closemodal();
            toast.success(mstringstore.getMstring('gradesreleased'));
        })
        .catch((error) => {
            window.console.error(error);
            closemodal();
            debug.value = error;
        });


    }

    /**
     * Remove override button has been clicked
     *
     */
    function removeoverride() {

        // Scale and grade are both 0 = remove override
        moodleFetch(
            'local_gugrades_write_additional_grade',
            {
                gradeitemid: props.itemid,
                userid: props.userid,
                admingrade: '',
                reason: 'CATEGORY',
                other: other.value,
                scale: 0,
                grade: 0,
                notes: '',
                delete: true,
            }
        )
        .then(() => {
            grade_added();
            recalculate_user();
            toast.success(mstringstore.getMstring('gradeadded'));
            closemodal();
        })
        .catch((error) => {
            window.console.error(error);
            debug.value = error;
            closemodal();
        });
    }
</script>