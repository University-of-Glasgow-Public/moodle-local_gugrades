

<template>
  <!-- Row -->
  <div
    role="row"
    class="grid items-center gap-2 rounded-md px-2 py-2 hover:bg-muted/40 transition-colors"
    style="grid-template-columns: 1fr 6rem 6rem"
  >
    <!-- Label cell with indent + vertical lines -->
    <div role="gridcell" class="flex items-center gap-1.5 min-w-0">
      <!-- Indent spacers — each level adds one spacer that carries the vertical line -->
      <div
        v-for="i in depth"
        :key="i"
        class="self-stretch flex-shrink-0 border-l border-red-500"
        style="width: 20px"
        aria-hidden="true"
      >
        <span class="absolute top-0 bottom-0 left-2.5 w-px bg-border" />
      </div>

      <!-- Icon -->
      <i
        :class="[
          depth === 0 ? 'ti ti-folders' : depth === 1 ? 'ti ti-folder' : 'ti ti-clipboard-list',
          'text-muted-foreground flex-shrink-0',
          depth === 0 ? 'text-base' : 'text-sm'
        ]"
        aria-hidden="true"
      />

      <!-- Name + badges -->
      <span
        class="truncate"
        :class="[
          depth === 0 ? 'text-sm font-medium text-foreground' : 'text-sm text-foreground',
          depth === 2 ? 'text-muted-foreground' : ''
        ]"
      >
        {{ node.label }}
      </span>

      <span
        v-if="isExam"
        class="flex-shrink-0 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300"
      >
        exam
      </span>
      <span
        v-if="isReassessable"
        class="flex-shrink-0 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300"
      >
        reassessable
      </span>
    </div>

    <!-- Exam toggle cell -->
    <div role="gridcell" class="flex items-center justify-center">
      <template v-if="canSetExam">
        <button
          type="button"
          role="switch"
          :aria-checked="isExam"
          :aria-label="`Mark '${node.label}' as an exam`"
          class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
          :class="isExam ? 'bg-violet-500' : 'bg-muted'"
          @click="isExam = !isExam"
        >
          <span
            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            :class="isExam ? 'translate-x-4' : 'translate-x-0'"
          />
        </button>
      </template>
      <span v-else class="text-muted-foreground/40 text-lg select-none" aria-label="Not applicable">—</span>
    </div>

    <!-- Reassessable checkbox cell -->
    <div role="gridcell" class="flex items-center justify-center">
      <template v-if="canSetReassessable">
        <input
          type="checkbox"
          :id="`reassess-${node.id}`"
          v-model="isReassessable"
          :aria-label="`Mark '${node.label}' as reassessable`"
          class="h-4 w-4 rounded border-border text-teal-600 focus:ring-teal-500 cursor-pointer accent-teal-600"
        />
      </template>
      <span v-else class="text-muted-foreground/40 text-lg select-none" aria-label="Not applicable">—</span>
    </div>
  </div>

  <!-- Children — recursive -->
  <template v-if="node.children?.length">
    <GradeCategoryRow
      v-for="child in node.children"
      :key="child.id"
      :node="child"
      :depth="depth + 1"
      :exam-state="examState"
      :reassess-state="reassessState"
      @update:exam-state="emit('update:examState', $event)"
      @update:reassess-state="emit('update:reassessState', $event)"
    />
  </template>
</template>

<script setup lang="ts">
    import { computed } from 'vue'

    export interface GradeNode {
        id: string
        label: string
        hasItems: boolean
        children: GradeNode[]
    }

    export type CategoryStateMap = Record<string, boolean>

    const props = defineProps<{
        node: GradeNode
        depth: number
        examState: CategoryStateMap
        reassessState: CategoryStateMap
    }>();

    const emit = defineEmits<{
        'update:examState': [value: CategoryStateMap]
        'update:reassessState': [value: CategoryStateMap]
    }>();

    const isExam = computed<boolean>({
        get: () => !!props.examState[props.node.id],
        set: (val: boolean) => {
            emit('update:examState', { ...props.examState, [props.node.id]: val })
        }
    });

    const isReassessable = computed<boolean>({
        get: () => !!props.reassessState[props.node.id],
        set: (val: boolean) => {
            emit('update:reassessState', { ...props.reassessState, [props.node.id]: val })
        }
    });

    // Exam toggle only available at level 1
    const canSetExam = computed<boolean>(() => props.depth === 1);

    // Reassessable checkbox only available on nodes that contain grade items
    const canSetReassessable = computed<boolean>(() => props.node.hasItems);
</script>
