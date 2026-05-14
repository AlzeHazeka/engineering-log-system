<script setup>
defineProps({
    label: String,
    value: [String, Number],
    hint: { type: String, default: "" },
});
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border p-5">
        <div class="text-2xl font-semibold text-gray-900">
            {{ value }}
        </div>
        <div class="text-sm text-gray-500 mt-1">
            {{ label }}
        </div>
        <div v-if="hint" class="text-xs text-gray-400 mt-2">
            {{ hint }}
        </div>
    </div>
</template>

