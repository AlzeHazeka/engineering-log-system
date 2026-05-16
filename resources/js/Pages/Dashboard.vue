<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { timeAgo } from "@/Utils/datetime";
import { Eye, FilePlus, Layers } from "lucide-vue-next";
import { useSoftRevalidate } from "@/Utils/softRevalidate";

const props = defineProps({
    systemsCount: Number,
    logsToday: Number,
    logsThisWeek: Number,
    logsThisMonth: Number,
    highCritical: Number,
    featureOnTimeRate: Number,
    bugOnTimeRate: Number,
    recentLogs: Array,
    criticalEvents: Array,
    systemsHealth: Object,
});

useSoftRevalidate({
    only: [
        "systemsCount",
        "logsToday",
        "logsThisWeek",
        "logsThisMonth",
        "highCritical",
        "featureOnTimeRate",
        "bugOnTimeRate",
        "recentLogs",
        "criticalEvents",
        "systemsHealth",
    ],
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 space-y-8">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        System Management Dashboard
                    </h2>
                    <p class="text-sm text-gray-500">
                        Activity summary and quick entry points.
                    </p>
                </div>

                <!-- KPI -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">Systems</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ systemsCount ?? 0 }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">Logs Today</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ logsToday ?? 0 }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">This Week</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ logsThisWeek ?? 0 }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">This Month</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ logsThisMonth ?? 0 }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">High / Critical</div>
                        <div class="text-3xl font-bold text-red-600 mt-1">
                            {{ highCritical ?? 0 }}
                        </div>
                    </div>
                </div>

                <!-- KPI: On-Time Performance -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">
                            Feature On-Time Rate
                        </div>
                        <div class="text-3xl font-bold mt-1">
                            {{ featureOnTimeRate ?? 0 }}%
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">
                            Bug Resolution On-Time Rate
                        </div>
                        <div class="text-3xl font-bold mt-1">
                            {{ bugOnTimeRate ?? 0 }}%
                        </div>
                    </div>
                </div>

                <!-- SYSTEM HEALTH -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="font-semibold mb-4">Systems Health</h3>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Active</span>
                                <span class="font-medium text-green-600">
                                    {{ systemsHealth?.active ?? 0 }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span>Maintenance</span>
                                <span class="font-medium text-yellow-600">
                                    {{ systemsHealth?.maintenance ?? 0 }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span>Deprecated</span>
                                <span class="font-medium text-gray-500">
                                    {{ systemsHealth?.deprecated ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="font-semibold mb-4">Quick Actions</h3>

                        <div class="flex items-center gap-2">
                            <a
                                :href="route('logs.create')"
                                class="inline-flex items-center justify-center rounded-lg bg-black text-white p-2 hover:opacity-90 transition"
                                title="Add log"
                                aria-label="Add log"
                            >
                                <FilePlus class="h-5 w-5" />
                            </a>

                            <a
                                :href="route('systems.create')"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                                title="Create system"
                                aria-label="Create system"
                            >
                                <Layers class="h-5 w-5" />
                            </a>

                            <a
                                :href="route('logs.index')"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                                title="View logs"
                                aria-label="View logs"
                            >
                                <Eye class="h-5 w-5" />
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="font-semibold mb-4">Critical Events</h3>

                        <div
                            v-for="log in criticalEvents"
                            :key="log?.id"
                            class="flex justify-between text-sm py-2 border-b last:border-0"
                        >
                            <div>{{ log.title }}</div>

                            <div class="text-red-500">
                                {{ timeAgo(log.logged_at) }}
                            </div>
                        </div>

                        <div
                            v-if="(criticalEvents?.length ?? 0) === 0"
                            class="text-gray-400 text-sm text-center py-4"
                        >
                            No critical events
                        </div>
                    </div>
                </div>

                <!-- Activity Summary -->
                <div class="bg-white rounded-xl shadow border p-6">
                    <h3 class="font-semibold mb-2">Activity Summary</h3>
                    <p class="text-sm text-gray-500">
                        Chart is temporarily disabled after database refactor.
                    </p>

                    <div
                        v-if="
                            (logsToday ?? 0) === 0 &&
                            (logsThisWeek ?? 0) === 0 &&
                            (logsThisMonth ?? 0) === 0
                        "
                        class="text-gray-400 text-sm text-center py-6"
                    >
                        Belum ada aktivitas
                    </div>

                    <div v-else class="text-sm text-gray-700 mt-4 space-y-1">
                        <div>Today: <span class="font-medium">{{ logsToday ?? 0 }}</span></div>
                        <div>This week: <span class="font-medium">{{ logsThisWeek ?? 0 }}</span></div>
                        <div>This month: <span class="font-medium">{{ logsThisMonth ?? 0 }}</span></div>
                    </div>
                </div>

                <!-- RECENT LOGS -->
                <div class="bg-white rounded-xl shadow border p-6">
                    <h3 class="font-semibold mb-4">Recent Logs</h3>

                    <div
                        v-for="log in recentLogs"
                        :key="log?.id"
                        class="flex justify-between items-center py-3 border-b last:border-0"
                    >
                        <div>
                            <div class="font-medium">
                                {{ log.title }}
                            </div>

                            <div class="text-xs text-gray-500">
                                {{ log.system?.name }}
                            </div>
                        </div>

                        <div class="text-sm text-gray-400">
                            {{ timeAgo(log.logged_at) }}
                        </div>
                    </div>

                    <div
                        v-if="(recentLogs?.length ?? 0) === 0"
                        class="text-gray-400 text-sm text-center py-6"
                    >
                        No recent logs
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
