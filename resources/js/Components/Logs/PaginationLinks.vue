<script setup>
defineProps({
    links: Array,
});
</script>

<template>
    <div class="flex gap-2">
        <a
            v-for="link in links"
            :key="link.label"
            v-html="link.label"
            :href="link.url || '#'"
            :class="[
                'px-3 py-1 rounded border',
                link.active
                    ? 'bg-black text-white'
                    : 'bg-white hover:bg-gray-100',
            ]"
        />
    </div>
</template>
