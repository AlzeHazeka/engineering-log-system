<script setup>
defineProps({
    links: Array,
});
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2">
        <a
            v-for="link in links"
            :key="link.label"
            v-html="link.label"
            :href="link.url || '#'"
            :class="[
                'px-2 sm:px-3 py-1.5 rounded border text-xs sm:text-sm',
                link.active
                    ? 'bg-black text-white'
                    : 'bg-white hover:bg-gray-100',
            ]"
        />
    </div>
</template>
