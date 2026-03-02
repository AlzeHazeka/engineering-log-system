<script setup>
import Badge from "@/Components/Badge.vue";

const props = defineProps({
    log: Object,
    logTypeMap: Object,
    logTypeLabel: Object,
    impactMap: Object,
    impactLabel: Object,
});

const emit = defineEmits(["delete"]);

const deleteLog = () => {
    emit("delete", props.log.id);
};
</script>

<template>
    <tr class="border-t hover:bg-gray-50">
        <td class="p-4">
            {{ new Date(log.logged_at).toLocaleTimeString() }}
        </td>

        <td class="p-4">
            {{ log.system.name }}
        </td>

        <td class="p-4 font-medium">
            {{ log.title }}
        </td>

        <td class="p-4">
            <Badge
                :label="logTypeLabel[log.type]"
                :colorClass="logTypeMap[log.type]"
            />
        </td>

        <td class="p-4">
            <Badge
                :label="impactLabel[log.impact]"
                :colorClass="impactMap[log.impact]"
            />
        </td>

        <td class="p-4 text-right space-x-3">
            <a
                :href="route('logs.show', log.id)"
                class="text-gray-600 hover:underline"
            >
                Show
            </a>

            <a
                :href="route('logs.edit', log.id)"
                class="text-blue-600 hover:underline"
            >
                Edit
            </a>

            <button @click="deleteLog" class="text-red-600 hover:underline">
                Delete
            </button>
        </td>
    </tr>
</template>
