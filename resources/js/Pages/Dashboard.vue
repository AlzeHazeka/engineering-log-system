<script setup>
import { onMounted, ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import ActivityHeatmap from "@/Components/ActivityHeatmap.vue";
import Chart from "chart.js/auto";

const props = defineProps({
    stats: Object,
    logsPerDay: Array,
    logsPerType: Array,
    logsPerImpact: Array,
    recentLogs: Array,
    criticalLogs: Array,
    systemHealth: Object,
    activityHeatmap: Array,
});

const dayChart = ref(null);
const typeChart = ref(null);
const impactChart = ref(null);

onMounted(() => {
    new Chart(dayChart.value, {
        type: "line",
        data: {
            labels: props.logsPerDay.map((d) => d.date),
            datasets: [
                {
                    label: "Logs",
                    data: props.logsPerDay.map((d) => d.total),
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                },
            ],
        },
        options: {
            plugins: {
                legend: { display: false },
            },
            scales: {
                y: { beginAtZero: true },
            },
        },
    });

    new Chart(typeChart.value, {
        type: "doughnut",
        data: {
            labels: props.logsPerType.map((t) => t.type),
            datasets: [
                {
                    data: props.logsPerType.map((t) => t.total),
                },
            ],
        },
        options: {
            plugins: { legend: { position: "bottom" } },
        },
    });

    new Chart(impactChart.value, {
        type: "doughnut",
        data: {
            labels: props.logsPerImpact.map((i) => i.impact),
            datasets: [
                {
                    data: props.logsPerImpact.map((i) => i.total),
                },
            ],
        },
        options: {
            plugins: { legend: { position: "bottom" } },
        },
    });
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">
                Engineering Dashboard
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 space-y-8">
                <!-- KPI -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">Systems</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ stats.totalSystems }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">Logs Today</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ stats.logsToday }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">This Week</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ stats.logsThisWeek }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">This Month</div>
                        <div class="text-3xl font-bold mt-1">
                            {{ stats.logsThisMonth }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">High / Critical</div>
                        <div class="text-3xl font-bold text-red-600 mt-1">
                            {{ stats.highCriticalThisMonth }}
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
                                    {{ systemHealth.active }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span>Maintenance</span>
                                <span class="font-medium text-yellow-600">
                                    {{ systemHealth.maintenance }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span>Deprecated</span>
                                <span class="font-medium text-gray-500">
                                    {{ systemHealth.deprecated }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="font-semibold mb-4">Quick Actions</h3>

                        <div class="flex flex-col gap-2">
                            <a
                                :href="route('logs.create')"
                                class="bg-black text-white text-sm px-3 py-2 rounded-lg text-center"
                            >
                                + Add Log
                            </a>

                            <a
                                :href="route('systems.create')"
                                class="border text-sm px-3 py-2 rounded-lg text-center"
                            >
                                + Create System
                            </a>

                            <a
                                :href="route('logs.index')"
                                class="border text-sm px-3 py-2 rounded-lg text-center"
                            >
                                View Logs
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="font-semibold mb-4">Critical Events</h3>

                        <div
                            v-for="log in criticalLogs"
                            :key="log.id"
                            class="flex justify-between text-sm py-2 border-b last:border-0"
                        >
                            <div>{{ log.title }}</div>

                            <div class="text-red-500">
                                {{ log.formatted_time }}
                            </div>
                        </div>

                        <div
                            v-if="criticalLogs.length === 0"
                            class="text-gray-400 text-sm text-center py-4"
                        >
                            No critical events
                        </div>
                    </div>
                </div>

                <!-- MAIN CHART -->
                <div class="bg-white rounded-xl shadow border p-6">
                    <h3 class="font-semibold mb-4">
                        Log Activity (Last 30 Days)
                    </h3>

                    <canvas ref="dayChart"></canvas>
                </div>

                <!-- HEATMAP -->
                <ActivityHeatmap :data="activityHeatmap" />

                <!-- DISTRIBUTION CHARTS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="font-semibold mb-4">Logs by Type</h3>

                        <canvas ref="typeChart"></canvas>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="font-semibold mb-4">Logs by Impact</h3>

                        <canvas ref="impactChart"></canvas>
                    </div>
                </div>

                <!-- RECENT LOGS -->
                <div class="bg-white rounded-xl shadow border p-6">
                    <h3 class="font-semibold mb-4">Recent Logs</h3>

                    <div
                        v-for="log in recentLogs"
                        :key="log.id"
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
                            {{ log.formatted_time }}
                        </div>
                    </div>

                    <div
                        v-if="recentLogs.length === 0"
                        class="text-gray-400 text-sm text-center py-6"
                    >
                        No recent logs
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
