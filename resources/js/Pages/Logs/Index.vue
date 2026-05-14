<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { router } from "@inertiajs/vue3";
import { Plus } from "lucide-vue-next";

import LogTable from "@/Components/Logs/LogTable.vue";

import {
    logTypeMap,
    logTypeLabel,
    impactMap,
    impactLabel,
    logStatusMap,
    logStatusLabel,
} from "@/Utils/enums";

const props = defineProps({
    logs: Object,
    system: Object,
});

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
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-900">
                        Logs
                    </h2>
                    <p v-if="system" class="text-sm text-gray-500">
                        System: {{ system.name }}
                    </p>
                    <p v-else class="text-sm text-gray-500">
                        Latest timeline entries.
                    </p>
                </div>

                <a
                    :href="route('logs.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-black text-white p-2 hover:opacity-90 transition"
                    title="Create log"
                    aria-label="Create log"
                >
                    <Plus class="h-5 w-5" />
                </a>
            </div>

            <LogTable
                :logs="logs"
                :logTypeMap="logTypeMap"
                :logTypeLabel="logTypeLabel"
                :impactMap="impactMap"
                :impactLabel="impactLabel"
                :logStatusMap="logStatusMap"
                :logStatusLabel="logStatusLabel"
                @delete="deleteLog"
            />
        </div>
    </AuthenticatedLayout>
</template>
