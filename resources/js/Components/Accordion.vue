<script setup>
defineProps({
    title: String,
    subtitle: { type: String, default: "" },
    open: { type: Boolean, default: false },
});
</script>

<template>
    <details
        class="bg-white rounded-xl shadow-sm border overflow-hidden group"
        :open="open"
    >
        <summary
            class="cursor-pointer select-none px-5 py-4 flex items-center justify-between hover:bg-gray-50"
        >
            <div>
                <div class="font-semibold text-gray-900">{{ title }}</div>
                <div v-if="subtitle" class="text-xs text-gray-500 mt-0.5">
                    {{ subtitle }}
                </div>
            </div>

            <div
                class="text-gray-400 group-open:rotate-180 transition-transform"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="w-5 h-5"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd"
                    />
                </svg>
            </div>
        </summary>

        <div class="px-5 pb-5">
            <slot />
        </div>
    </details>
</template>

