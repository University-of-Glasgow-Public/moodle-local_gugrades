import type { InjectionKey, Ref } from 'vue';

export interface GradeDialogSelection {
    userid: number;
    owner: symbol;
}

export const gradeDialogSelectionKey: InjectionKey<Ref<GradeDialogSelection | null>> = Symbol('gradeDialogSelection');
