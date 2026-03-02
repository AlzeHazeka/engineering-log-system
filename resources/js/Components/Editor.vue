<script setup>
import { shallowRef, watch, onBeforeUnmount } from "vue";
import { Editor, EditorContent } from "@tiptap/vue-3";

import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue"]);

const editor = shallowRef(null);

editor.value = new Editor({
    content: props.modelValue || "<p></p>",
    extensions: [StarterKit, Underline],
    onUpdate: ({ editor }) => {
        emit("update:modelValue", editor.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        if (value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value || "<p></p>", false);
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div class="border rounded-xl bg-white overflow-hidden">
        <div class="flex flex-wrap gap-2 p-3 border-b bg-gray-50 text-sm">
            <button
                type="button"
                :class="{ 'text-blue-600 font-bold': editor?.isActive('bold') }"
                @click="editor.chain().focus().toggleBold().run()"
            >
                B
            </button>

            <button
                type="button"
                :class="{ 'text-blue-600 italic': editor?.isActive('italic') }"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                I
            </button>

            <button
                type="button"
                :class="{
                    'text-blue-600 underline': editor?.isActive('underline'),
                }"
                @click="editor.chain().focus().toggleUnderline().run()"
            >
                U
            </button>

            <button
                type="button"
                :class="{ 'text-blue-600': editor?.isActive('bulletList') }"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                • List
            </button>

            <button
                type="button"
                :class="{ 'text-blue-600': editor?.isActive('orderedList') }"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                1. List
            </button>

            <button
                type="button"
                :class="{ 'text-blue-600': editor?.isActive('blockquote') }"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                Quote
            </button>

            <button
                type="button"
                @click="editor.commands.setContent('<p></p>')"
            >
                Clear
            </button>
        </div>

        <EditorContent
            :editor="editor"
            class="p-4 min-h-[200px] prose max-w-none focus:outline-none"
        />
    </div>
</template>
