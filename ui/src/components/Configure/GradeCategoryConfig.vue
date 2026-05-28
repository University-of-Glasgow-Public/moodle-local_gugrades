

<template>
  <div class="p-6 max-w-2xl">

    <!-- Header -->
    <div class="mb-5">
        <h2 class="text-base font-medium text-foreground">Grade category settings</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            Configure which categories contain reassessable items and which represent exams.
        </p>
    </div>

    <!-- Legend -->
    <div class="mb-4 flex flex-wrap gap-x-5 gap-y-2 rounded-lg bg-muted/50 px-4 py-2.5 text-xs text-muted-foreground">
        <span class="flex items-center gap-1.5">
            <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-900/40 dark:text-violet-300">exam</span>
            Second-level categories only
        </span>
        <span class="flex items-center gap-1.5">
            <span class="inline-flex rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-800 dark:bg-teal-900/40 dark:text-teal-300">reassessable</span>
            Categories that contain grade items
        </span>
    </div>

    <!-- Column headers -->
    <div
      class="grid items-center gap-2 border-b border-border px-2 pb-2 text-xs font-medium text-muted-foreground"
      style="grid-template-columns: 1fr 6rem 6rem"
    >
        <span>Category</span>
        <span class="text-center leading-tight">
            Exam<br>
            <span class="font-normal opacity-70">2nd level only</span>
        </span>
        <span class="text-center">Reassessable</span>
    </div>

    <!-- Tree -->
    <div role="treegrid" aria-label="Grade categories" class="mt-1">
        <GradeCategoryRow
            v-for="node in tree"
            :key="node.id"
            :node="node"
            :depth="0"
            :exam-state="examState"
            :reassess-state="reassessState"
            @update:exam-state="examState = $event"
            @update:reassess-state="reassessState = $event"
        />
    </div>

  </div>
</template>

<script setup lang="ts">
    import { ref } from 'vue'
    import GradeCategoryRow, { type GradeNode, type CategoryStateMap } from './GradeCategoryRow.vue'

    // Example tree — replace with your actual data source
    const tree = ref<GradeNode[]>([
    {
        id: 'summative',
        label: 'Summative',
        hasItems: false,
        children: [
        {
            id: 'class-assessments',
            label: 'Class assessments',
            hasItems: false,
            children: [
            { id: 'assign-1', label: 'First assignment',  hasItems: true, children: [] },
            { id: 'assign-2', label: 'Second assignment', hasItems: true, children: [] }
            ]
        },
        { id: 'summer-exam',   label: 'Summer exam',   hasItems: true, children: [] },
        { id: 'december-exam', label: 'December exam', hasItems: true, children: [] }
        ]
    },
    {
        id: 'formative',
        label: 'Formative',
        hasItems: false,
        children: [
        { id: 'quizzes',       label: 'Quizzes',       hasItems: true, children: [] },
        { id: 'participation', label: 'Participation', hasItems: true, children: [] }
        ]
    }
    ])

    // Flat state maps — keyed by node id
    const examState = ref<CategoryStateMap>({
        'summer-exam':   false,
        'december-exam': false,
        'quizzes':       false,
        'participation': false
    });

    const reassessState = ref<CategoryStateMap>({
        'assign-1':      false,
        'assign-2':      false,
        'summer-exam':   false,
        'december-exam': false,
        'quizzes':       false,
        'participation': false
    });
</script>
