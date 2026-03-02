<script setup>
defineProps({
    label: String,
    colorClass: String,
});
</script>

<template>
    <span :class="['px-2 py-1 text-xs rounded-full font-medium', colorClass]">
        {{ label }}
    </span>
</template>
