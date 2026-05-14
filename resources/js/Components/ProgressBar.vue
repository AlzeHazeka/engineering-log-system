<script setup>
defineProps({
    value: { type: Number, default: 0 },
});

const clamp = (n) => Math.max(0, Math.min(100, Number(n || 0)));
</script>

<template>
    <div class="w-full">
        <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
            <div
                class="h-full rounded-full bg-gray-900"
                :style="{ width: clamp(value) + '%' }"
            />
        </div>

        <div class="mt-1 text-xs text-gray-500">
            {{ clamp(value) }}%
        </div>
    </div>
</template>

