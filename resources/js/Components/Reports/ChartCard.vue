<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import Chart from "chart.js/auto";

const props = defineProps({
    title: { type: String, default: "" },
    subtitle: { type: String, default: "" },
    type: { type: String, required: true },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    height: { type: Number, default: 260 },
});

const canvasRef = ref(null);
let chartInstance = null;

const renderChart = () => {
    if (!canvasRef.value) return;

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    const ctx = canvasRef.value.getContext("2d");
    if (!ctx) return;

    chartInstance = new Chart(ctx, {
        type: props.type,
        data: props.data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: "bottom" },
                tooltip: { enabled: true },
            },
            ...props.options,
        },
    });
};

onMounted(renderChart);
watch(() => [props.type, props.data, props.options], renderChart, { deep: true });
onBeforeUnmount(() => chartInstance?.destroy());
</script>

<template>
    <div class="border border-slate-200 rounded-lg p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-slate-900">
                    {{ title }}
                </div>
                <div v-if="subtitle" class="mt-1 text-xs text-slate-500">
                    {{ subtitle }}
                </div>
            </div>
            <slot name="actions" />
        </div>

        <div class="mt-4 relative" :style="{ height: height + 'px' }">
            <canvas ref="canvasRef" />
        </div>
    </div>
</template>

