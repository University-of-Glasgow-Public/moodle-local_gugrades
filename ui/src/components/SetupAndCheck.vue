<template>
    <DebugDisplay :debug="debug"></DebugDisplay>

    <VueModal :model-value="showModalOpen" :enableClose="false" modalClass="rounded max-w-3xl" :title="modalTitle" @update:modelValue="() => {}">
        <UAlert v-if="showerrors" variant="error">
            <div class="mb-4">
                A data integrity check has found invalid data in MyGrades. This is probably due to changing the course start
                date or manipulating Gradebook settings AFTER grades have already been imported. MyGrades cannot continue.
            </div>
            <div class="table w-full border-separate border-spacing-y-2 border-spacing-x-4">
                <div v-for="error in errors" :key="errorKey(error)" class="table-row items-center">
                    <div class="table-cell whitespace-nowrap font-bold align-middle">{{ error.itemname }}</div>
                    <div class="table-cell align-middle text-slate-600">{{ error.error }}</div>
                    <div class="table-cell align-middle text-right w-44">
                        <ResetUserGradesButton
                            v-if="error.errortype === 'unenrolled_user' && error.userid"
                            :userid="error.userid"
                            :small="true"
                            @reset="reload"
                        />
                        <ResetAssessmentButton
                            v-else-if="error.gradeitemid"
                            :itemid="error.gradeitemid"
                            :small="true"
                            @reset="reload"
                        />
                    </div>
                </div>
                <div v-if="courseurl" class="table-row">
                    <div class="table-cell"></div>
                    <div class="table-cell"></div>
                    <div class="table-cell align-middle text-right w-44">
                        <MenuButton :href="courseurl" :wide="true" iconName="MoveLeft">
                            {{ returnToCourseLabel }}
                        </MenuButton>
                    </div>
                </div>
            </div>
        </UAlert>
        <UAlert v-else-if="shownotices" variant="warning">
            <div class="mb-4">{{ noticeIntro }}</div>
            <ul class="list-disc pl-5 mb-4 space-y-1">
                <li v-for="notice in notices" :key="notice.itemname">
                    {{ notice.message }}
                </li>
            </ul>
            <div class="text-right">
                <UButton variant="primary" @click="continueSetup">{{ continueLabel }}</UButton>
            </div>
        </UAlert>
        <UAlert v-else>
            Setting up MyGrades and checking data integrity.
        </UAlert>
    </VueModal>
</template>

<script setup lang="ts">
    import { ref, onMounted, computed } from 'vue';
    import { storeToRefs } from 'pinia';
    import { useMstrings } from '@/stores/mstrings.js';
    import { moodleFetch } from '@/js/moodlefetch';
    import UAlert from './Common/UAlert.vue';
    import UButton from './Common/UButton.vue';
    import DebugDisplay from '@/components/Common/DebugDisplay.vue';
    import { useActivityTreeStore } from '../stores/activitytree.js';
    import { usePopulateTrees } from '@/js/setuptrees.ts';
    import ResetAssessmentButton from './Capture/ResetAssessmentButton.vue';
    import ResetUserGradesButton from './Capture/ResetUserGradesButton.vue';
    import MenuButton from './Common/MenuButton.vue';
    import { useToast } from 'vue-toastification';

    interface iError {
        gradeitemid: number;
        itemname: string;
        error: string;
        errortype?: string;
        userid?: number;
    }

    interface iNotice {
        itemname: string;
        message: string;
    }

    const treestore = useActivityTreeStore();
    const populatetrees = usePopulateTrees();
    const mstringstore = useMstrings();
    const { mstrings } = storeToRefs(mstringstore);
    const toast = useToast();
    
    const debug = ref({});
    const errors = ref<iError[]>([]);
    const notices = ref<iNotice[]>([]);
    const showerrors = ref(false);
    const shownotices = ref(false);
    const isSettingUp = ref(true);
    const setupFinished = ref(false);
    const courseurl = ref('');

    const showModalOpen = computed(() => !setupFinished.value && (isSettingUp.value || showerrors.value || shownotices.value));

    const modalTitle = computed(() => {
        if (shownotices.value) {
            return mstrings.value.integrity_reassessment_notice_title || 'Reassessment updated';
        }
        return 'Setup and checking';
    });

    const noticeIntro = computed(() =>
        mstrings.value.integrity_reassessment_notice_intro
        || 'The gradebook structure has changed since reassessment was configured. MyGrades has automatically removed reassessment from the following categories:'
    );

    const continueLabel = computed(() => mstrings.value.integritycontinue || 'Continue');

    const returnToCourseLabel = computed(() => mstrings.value.integrity_return_to_course || 'Return to course');

    function showReassessmentNotices() {
        notices.value.forEach((notice) => {
            toast.warning(notice.message, { timeout: 15000 });
        });
    }

    async function continueSetup() {
        shownotices.value = false;
        isSettingUp.value = true;

        try {
            await populatetrees.populate();
            setupFinished.value = true;
            isSettingUp.value = false;
        } catch (error: any) {
            debug.value = error;
            console.error(error);
        }
    }

    onMounted(async () => {
        try {
            treestore.ready = false;

            const [result, courseinfo]: any[] = await Promise.all([
                moodleFetch('local_gugrades_check_integrity', {}),
                moodleFetch('local_gugrades_get_course_info', {}),
            ]);
            courseurl.value = courseinfo.url || '';
            errors.value = result.erroritems || [];
            notices.value = result.reassessmentnotices || [];

            if (notices.value.length > 0) {
                showReassessmentNotices();
            }

            if (errors.value.length > 0) {
                showerrors.value = true;
                isSettingUp.value = false;
                return;
            }

            if (notices.value.length > 0) {
                shownotices.value = true;
                isSettingUp.value = false;
                return;
            }

            await continueSetup();

        } catch (error: any) {
            debug.value = error;
            console.error(error);
        }
    });

    function reload() {
        window.location.reload();
    }

    function errorKey(error: iError) {
        if (error.errortype === 'unenrolled_user') {
            return `user-${error.userid}`;
        }
        return `item-${error.gradeitemid}-${error.errortype || 'default'}`;
    }
</script>
