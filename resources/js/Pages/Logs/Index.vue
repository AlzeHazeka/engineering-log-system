<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { router } from "@inertiajs/vue3";

import LogFilterBar from "@/Components/Logs/LogFilterBar.vue";
import LogTable from "@/Components/Logs/LogTable.vue";

import {
    logTypeMap,
    logTypeLabel,
    impactMap,
    impactLabel,
} from "@/Utils/enums";

const props = defineProps({
    logs: Object,
    filters: Object,
    systems: Array,
});

const applyFilter = (filters) => {
    router.get(route("logs.index"), filters, {
        preserveState: true,
        replace: true,
    });
};

const resetFilter = () => {
    router.get(route("logs.index"), {
        date: new Date().toISOString().slice(0, 10),
    });
};

const deleteLog = (id) => {
    if (confirm("Delete this log?")) {
        router.delete(route("logs.destroy", id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Logs
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 space-y-6">
                <LogFilterBar
                    :filters="filters"
                    :systems="systems"
                    :logTypeLabel="logTypeLabel"
                    :impactLabel="impactLabel"
                    @update="applyFilter"
                    @reset="resetFilter"
                />

                <LogTable
                    :logs="logs"
                    :logTypeMap="logTypeMap"
                    :logTypeLabel="logTypeLabel"
                    :impactMap="impactMap"
                    :impactLabel="impactLabel"
                    @delete="deleteLog"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
