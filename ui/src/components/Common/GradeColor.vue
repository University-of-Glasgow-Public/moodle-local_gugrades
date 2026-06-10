<template>
    <span :class="gradecolorclass(grade).concat(otherclasses)">{{ grade }}</span>
</template>

<script setup lang="ts">
    import { gradecolors } from '@/js/GradeColors';

    const props = withDefaults(
    defineProps<{
        grade: string;
        size?: string;
        otherclasses?: string[];
    }>(),
        {
            otherclasses: () => [], // Default to empty array
        }
    );

    /**
     * Work out the fancy color class for grades
     */
    function gradecolorclass(grade: string): string[] {
        let colorclass: string[] = [];

        // Only look at the first two characters of the grade
        const grade2 = grade.substring(0, 2);

        if (grade2 in gradecolors) {
            const classes = gradecolors[grade2]!;
            colorclass = ['px-2.5', 'py-0.5', 'rounded-md', 'font-semibold', 'inline-block'];
            if (props.size) {
                colorclass.push(props.size);
            } else {
                colorclass.push('text-sm');
            }
            colorclass.push(classes.bg);
            colorclass.push(classes.text);
        }

        return colorclass;
    }
</script>