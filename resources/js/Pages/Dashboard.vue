<script setup>
import { onMounted, ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Chart from "chart.js/auto";

const props = defineProps({
    stats: Object,
    logsPerDay: Array,
    logsPerType: Array,
});

const dayChart = ref(null);
const typeChart = ref(null);

onMounted(() => {
    // Logs per Day Chart
    new Chart(dayChart.value, {
        type: "line",
        data: {
            labels: props.logsPerDay.map((d) => d.date),
            datasets: [
                {
                    label: "Logs",
                    data: props.logsPerDay.map((d) => d.total),
                    borderWidth: 2,
                    tension: 0.3,
                },
            ],
        },
    });

    // Logs per Type Chart
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
    });
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 space-y-8">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">Systems</div>
                        <div class="text-2xl font-bold">
                            {{ stats.totalSystems }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">Logs Today</div>
                        <div class="text-2xl font-bold">
                            {{ stats.logsToday }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">This Week</div>
                        <div class="text-2xl font-bold">
                            {{ stats.logsThisWeek }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">This Month</div>
                        <div class="text-2xl font-bold">
                            {{ stats.logsThisMonth }}
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <div class="text-sm text-gray-500">High/Critical</div>
                        <div class="text-2xl font-bold text-red-600">
                            {{ stats.highCriticalThisMonth }}
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="mb-4 font-semibold">Logs This Month</h3>
                        <canvas ref="dayChart"></canvas>
                    </div>

                    <div class="bg-white rounded-xl shadow border p-6">
                        <h3 class="mb-4 font-semibold">Logs by Type</h3>
                        <canvas ref="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
