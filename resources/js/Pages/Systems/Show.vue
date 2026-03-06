<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Badge from "@/Components/Badge.vue";
import {
    systemStatusMap,
    systemStatusLabel,
    logTypeMap,
    logTypeLabel,
    impactMap,
    impactLabel,
} from "@/Utils/enums";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    system: Object,
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">
                {{ system.name }}
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 space-y-6">
                <!-- System Info Card -->
                <div class="bg-white rounded-xl shadow border p-8 space-y-4">
                    <div
                        class="flex flex-col md:flex-row md:justify-between md:items-center gap-4"
                    >
                        <Badge
                            :label="systemStatusLabel[system.status]"
                            :colorClass="systemStatusMap[system.status]"
                        />

                        <div class="text-sm text-gray-500">
                            Total Logs: {{ system.logs_count }}
                        </div>
                    </div>

                    <div v-if="system.repository_url">
                        <a
                            :href="system.repository_url"
                            target="_blank"
                            class="text-blue-600 hover:underline"
                        >
                            {{ system.repository_url }}
                        </a>
                    </div>

                    <div v-if="system.description" class="text-gray-600">
                        {{ system.description }}
                    </div>

                    <div class="flex gap-4 pt-4">
                        <a
                            :href="route('systems.edit', system.slug)"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg"
                        >
                            Edit
                        </a>

                        <a
                            :href="route('systems.index')"
                            class="border px-4 py-2 rounded-lg"
                        >
                            Back
                        </a>
                    </div>
                </div>

                <!-- Recent Logs -->
                <div class="bg-white rounded-xl shadow border overflow-hidden">
                    <div class="p-6 border-b font-semibold">Recent Logs</div>

                    <div v-if="system.logs.length > 0">
                        <Link
                            v-for="log in system.logs"
                            :key="log.id"
                            :href="route('logs.show', log.id)"
                            class="block p-4 border-b hover:bg-gray-50 transition"
                        >
                            <div
                                class="flex flex-col md:flex-row md:justify-between gap-2"
                            >
                                <div>
                                    <div class="font-medium">
                                        {{ log.title }}
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ log.formatted_datetime }}
                                    </div>
                                </div>

                                <div class="flex gap-2 flex-wrap">
                                    <Badge
                                        :label="logTypeLabel[log.type]"
                                        :colorClass="logTypeMap[log.type]"
                                    />

                                    <Badge
                                        :label="impactLabel[log.impact]"
                                        :colorClass="impactMap[log.impact]"
                                    />
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="p-6 text-gray-400 text-center">
                        No logs yet for this system.
                    </div>
                </div>

                <div class="p-4 text-right">
                    <Link
                        :href="route('logs.index', { system: system.id })"
                        class="text-blue-600 hover:underline text-sm"
                    >
                        View all logs →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
