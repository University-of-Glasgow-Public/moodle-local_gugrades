<template>
    <span 
        :class="[gradecolorclass(grade), otherclasses]" 
        class="inline-flex items-center justify-center h-6 text-start text-nowrap px-1"
    >
        {{ grade }}
    </span>
</template>

<script setup lang="ts">
    import { gradecolors } from '@/js/GradeColors';

    const props = withDefaults(
    defineProps<{
        grade: string;
        size?: string;
        strikethrough?: boolean,
        otherclasses?: string[];
    }>(),
        {
            otherclasses: () => [],
            strikethrough: false,
        }
    );

    function gradecolorclass(grade: string): string[] {
        let colorclass: string[] = [];

        const cleanGrade = grade.startsWith('X') ? grade.slice(1) : grade;
        const grade2 = cleanGrade.substring(0, 2);

        if (grade2 in gradecolors) {
            const classes = gradecolors[grade2]!;
            
            // 🚨 REMOVED 'inline-block' and 'px-2.5' from here so they don't break the layout!
            colorclass = ['py-0.5', 'rounded-md', 'font-bold'];
            
            if (props.size) {
                colorclass.push(props.size);
            } else {
                colorclass.push('text-xs'); // Tweaked to text-xs so "E1:8" fits neatly inside 36px
            }
            colorclass.push('min-w-10');
            colorclass.push(classes.bg);
            colorclass.push(classes.text);
        }

        if (props.strikethrough) {
            colorclass.push('line-through');
        }

        return colorclass;
    }
</script>
