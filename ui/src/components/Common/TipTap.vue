
<template>
    <div class="border border-gray-200 rounded-md overflow-hidden">
        <div v-if="editor" class="flex gap-1 p-2 border-b border-gray-200 bg-gray-50">
            <button
                @click="editor.chain().focus().toggleBold().run()"
                :class="editor.isActive('bold') ? 'bg-gray-200' : ''"
                class="px-2 py-1 rounded text-sm font-bold hover:bg-gray-200"
            >B</button>
            <button
                @click="editor.chain().focus().toggleItalic().run()"
                :class="editor.isActive('italic') ? 'bg-gray-200' : ''"
                class="px-2 py-1 rounded text-sm italic hover:bg-gray-200"
            >I</button>
            <button
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="editor.isActive('bulletList') ? 'bg-gray-200' : ''"
                class="px-2 py-1 rounded text-sm hover:bg-gray-200"
            >• List</button>
        </div>
        <EditorContent
            :editor="editor"
            class="prose prose-sm max-w-none p-1 min-h-32 [&_.ProseMirror]:outline-none"
        />
    </div>
</template>

<script setup lang="ts">
    import { onBeforeUnmount } from 'vue'
    import { useEditor, EditorContent } from '@tiptap/vue-3'
    import StarterKit from '@tiptap/starter-kit'

    const props = defineProps<{
        modelValue: string
    }>();

    const emit = defineEmits<{
        (e: 'update:modelValue', value: string): void
    }>();

    const editor = useEditor({
        content: props.modelValue,
        extensions: [StarterKit],
        onUpdate: ({ editor }) => {
            emit('update:modelValue', editor.getHTML())
        }
    });

    onBeforeUnmount(() => {
        editor.value?.destroy()
    });
</script>

