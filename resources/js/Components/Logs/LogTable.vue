<script setup>
import LogRow from "./LogRow.vue";
import PaginationLinks from "./PaginationLinks.vue";

defineProps({
    logs: Object,
    logTypeMap: Object,
    logTypeLabel: Object,
    impactMap: Object,
    impactLabel: Object,
    logStatusMap: Object,
    logStatusLabel: Object,
});

const emit = defineEmits(["delete"]);
</script>

<template>
    <div class="bg-white rounded-xl shadow border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="p-4">Time</th>
                        <th class="p-4">System</th>
                        <th class="p-4">Feature</th>
                        <th class="p-4">Title</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Impact</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right w-32 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <LogRow
                        v-for="log in logs.data"
                        :key="log.id"
                        :log="log"
                        :logTypeMap="logTypeMap"
                        :logTypeLabel="logTypeLabel"
                        :impactMap="impactMap"
                        :impactLabel="impactLabel"
                        :logStatusMap="logStatusMap"
                        :logStatusLabel="logStatusLabel"
                        @delete="emit('delete', $event)"
                    />

                    <tr v-if="logs.data.length === 0">
                        <td colspan="8" class="p-6 text-center text-gray-400">
                            No logs found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t flex justify-between items-center text-sm">
            <div>Showing {{ logs.from || 0 }} to {{ logs.to || 0 }}</div>

            <PaginationLinks :links="logs.links" />
        </div>
    </div>
</template>
