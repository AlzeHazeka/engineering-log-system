<script setup>
defineProps({
    variant: { type: String, default: "color" }, // color | white
    sizeClass: { type: String, default: "h-8 w-8" },
});
</script>

<template>
    <img
        :src="
            variant === 'white'
                ? '/Rumah%20Atsiri%20Indonesia%20Logogram%20White.png'
                : '/Rumah%20Atsiri%20Indonesia%20Logogram%20Black.png'
        "
        :class="sizeClass"
        alt="System Management Workstation"
    />
</template>
