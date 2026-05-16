<script setup>
import { computed, reactive, ref, nextTick } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Badge from "@/Components/Badge.vue";
import StatCard from "@/Components/StatCard.vue";
import { router } from "@inertiajs/vue3";
import html2canvas from "html2canvas";
import ChartCard from "@/Components/Reports/ChartCard.vue";
import { formatDayDate } from "@/Utils/datetime";
import DateField from "@/Components/DateField.vue";
import {
    getBugFixMeetingStatus,
    getProgressMeetingStatus,
    getOperationalInsights,
    getDerivedMetrics,
} from "@/Utils/reportStatus";

import {
    logTypeMap,
    logTypeLabel,
    impactMap,
    impactLabel,
    logStatusMap,
    logStatusLabel,
} from "@/Utils/enums";

const props = defineProps({
    filters: Object,
    systems: Array,
    features: Array,
    kpis: Object,
    filter_feedback: Object,
    bug_summary: Object,
    highlights: Object,
    activity: Array,
    log_table: Array,
    grouping: Object,
    charts: Object,
});

const exporting = ref(false);
const reportRef = ref(null);
const kpiRef = ref(null);
const visualRef = ref(null);

const systemPickerOpen = ref(false);
const featurePickerOpen = ref(false);
const systemSearch = ref("");
const featureSearch = ref("");

const form = reactive({
    start_date: props.filters?.start_date ?? "",
    end_date: props.filters?.end_date ?? "",
    system_id: props.filters?.system_id ?? "",
    feature_id: props.filters?.feature_id ?? "",
    types: props.filters?.types ?? ["progress", "bug", "fix"],
    view_mode: props.filters?.view_mode ?? "detail",
    report_view: !!props.filters?.report_view,
    progress_status_period: !!props.filters?.progress_status_period,
    completion_trend_period: !!props.filters?.completion_trend_period,
    backlog_trend_period: !!props.filters?.backlog_trend_period,
});

const filenameSuffix = computed(() => {
    const start = form.start_date || "start";
    const end = form.end_date || "end";
    return `${start}_${end}`;
});

const filterFeedbackText = computed(() => {
    const total = props.filter_feedback?.total_logs ?? props.kpis?.total_logs ?? 0;
    const systems = props.filter_feedback?.system_count ?? 0;
    const start = form.start_date ? formatDate(form.start_date) : "—";
    const end = form.end_date ? formatDate(form.end_date) : "—";
    return `Menampilkan ${total} log dari ${systems} sistem pada periode ${start} – ${end}`;
});

const slaTone = computed(() => {
    const rate = props.kpis?.bug_sla_on_time_rate ?? 0;
    if (rate >= 80) return "text-green-700 bg-green-50 border-green-200";
    if (rate >= 50) return "text-yellow-800 bg-yellow-50 border-yellow-200";
    return "text-red-700 bg-red-50 border-red-200";
});

const exportPdf = () => {
    const params = new URLSearchParams();
    if (form.start_date) params.set("start_date", form.start_date);
    if (form.end_date) params.set("end_date", form.end_date);
    if (form.system_id) params.set("system_id", form.system_id);
    if (form.feature_id) params.set("feature_id", form.feature_id);
    (form.types || []).forEach((t) => params.append("types[]", t));
    params.set("format", "pdf");
    window.location.href = `${route("reports.export")}?${params.toString()}`;
};

const captureElement = async (element) => {
    const el = element?.value ?? element;
    if (!el) return null;

    return await html2canvas(el, {
        backgroundColor: "#ffffff",
        logging: false,
        scale: 2,
        scrollX: 0,
        scrollY: 0,
        windowWidth: el.scrollWidth,
        windowHeight: el.scrollHeight,
        ignoreElements: (el) => el?.dataset?.exportIgnore === "true",
    });
};

const downloadPng = async (element, filename) => {
    if (exporting.value) return;
    exporting.value = true;
    try {
        const canvas = await captureElement(element);
        if (!canvas) {
            alert("Gagal export PNG. Coba lagi setelah halaman selesai termuat.");
            return;
        }
        const dataUrl = canvas.toDataURL("image/png");
        const link = document.createElement("a");
        link.href = dataUrl;
        link.download = filename;
        link.click();
    } finally {
        exporting.value = false;
    }
};

const exportSummaryPng = async () => {
    const prev = form.view_mode;
    try {
        form.view_mode = "summary";
        await nextTick();
        await new Promise((r) => setTimeout(r, 120));
        await downloadPng(
            reportRef,
            `report-summary_${filenameSuffix.value}.png`
        );
    } finally {
        form.view_mode = prev;
    }
};

const exportVisualPng = async () => {
    const prev = form.view_mode;
    try {
        form.view_mode = "visual";
        await nextTick();
        await new Promise((r) => setTimeout(r, 200));
        await downloadPng(visualRef, `report-visual_${filenameSuffix.value}.png`);
    } finally {
        form.view_mode = prev;
    }
};

// PDF export is handled server-side via `/reports/export?format=pdf`.

const featureOptions = computed(() => {
    const systemSelected = !!form.system_id;
    return (props.features ?? []).map((f) => ({
        ...f,
        optionLabel: systemSelected
            ? f.title
            : f.system_name
            ? `${f.system_name} — ${f.title}`
            : f.title,
    }));
});

const selectedSystemName = computed(() => {
    if (!form.system_id) return "Semua System";
    const found = (props.systems ?? []).find((s) => String(s.id) === String(form.system_id));
    return found?.name ?? "Semua System";
});

const selectedFeatureName = computed(() => {
    if (!form.feature_id) return "Semua Feature";
    const found = (featureOptions.value ?? []).find((f) => String(f.id) === String(form.feature_id));
    return found?.optionLabel ?? "Semua Feature";
});

const systemPickerList = computed(() => {
    const q = systemSearch.value.trim().toLowerCase();
    const base = props.systems ?? [];
    if (!q) return base;
    return base.filter((s) => String(s.name || "").toLowerCase().includes(q));
});

const featurePickerList = computed(() => {
    const q = featureSearch.value.trim().toLowerCase();
    const base = featureOptions.value ?? [];
    if (!q) return base;
    return base.filter((f) => String(f.optionLabel || "").toLowerCase().includes(q));
});

const pickSystem = (id) => {
    form.system_id = id || "";
    form.feature_id = "";
    systemPickerOpen.value = false;
    systemSearch.value = "";
    applyFilters();
};

const pickFeature = (id) => {
    form.feature_id = id || "";
    featurePickerOpen.value = false;
    featureSearch.value = "";
    applyFilters();
};

const progressStatusChart = computed(() => {
    const onProgress = props.charts?.progress_status?.on_progress ?? 0;
    const done = props.charts?.progress_status?.done ?? 0;

    return {
        labels: ["On Progress", "Done"],
        datasets: [
            {
                data: [onProgress, done],
                backgroundColor: [
                    "rgba(175, 67, 36, 0.28)", // terracotta
                    "rgba(34, 197, 94, 0.28)", // green
                ],
                borderColor: [
                    "rgba(175, 67, 36, 0.85)",
                    "rgba(34, 197, 94, 0.85)",
                ],
                borderWidth: 1,
            },
        ],
    };
});

const impactDistributionChart = computed(() => {
    const dist = props.charts?.impact_distribution ?? {};
    const order = ["critical", "high", "medium", "low"];
    const labels = [];
    const data = [];
    const colors = [];
    const borders = [];

    const add = (key, label, bg, border) => {
        const v = Number(dist?.[key] ?? 0);
        if (!v) return;
        labels.push(label);
        data.push(v);
        colors.push(bg);
        borders.push(border);
    };

    add("critical", "Critical", "rgba(239, 68, 68, 0.28)", "rgba(239, 68, 68, 0.85)");
    add("high", "High", "rgba(249, 115, 22, 0.28)", "rgba(249, 115, 22, 0.85)");
    add("medium", "Medium", "rgba(59, 130, 246, 0.22)", "rgba(59, 130, 246, 0.8)");
    add("low", "Low", "rgba(148, 163, 184, 0.28)", "rgba(100, 116, 139, 0.85)");
    // Show unknown only when it exists to avoid noise.
    add("unknown", "Unknown", "rgba(100, 116, 139, 0.18)", "rgba(100, 116, 139, 0.7)");

    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor: colors,
                borderColor: borders,
                borderWidth: 1,
            },
        ],
    };
});

const completionTrendChart = computed(() => {
    const labels = props.charts?.completion_trend?.labels ?? [];
    const progressDone = props.charts?.completion_trend?.progress_done ?? [];
    const fixResolved = props.charts?.completion_trend?.fix_resolved ?? [];

    return {
        labels,
        datasets: [
            {
                label: "Progress Done",
                data: progressDone,
                borderColor: "rgba(34, 197, 94, 0.85)",
                backgroundColor: "rgba(34, 197, 94, 0.12)",
                tension: 0.35,
                fill: true,
                pointRadius: 2,
            },
            {
                label: "Fix Resolved",
                data: fixResolved,
                borderColor: "rgba(175, 67, 36, 0.85)",
                backgroundColor: "rgba(175, 67, 36, 0.10)",
                tension: 0.35,
                fill: true,
                pointRadius: 2,
            },
        ],
    };
});

const backlogTrendChart = computed(() => {
    const labels = props.charts?.open_backlog_trend?.labels ?? [];
    const openBug = props.charts?.open_backlog_trend?.open_bug ?? [];
    const openProgress = props.charts?.open_backlog_trend?.open_progress ?? [];

    return {
        labels,
        datasets: [
            {
                label: "Open Bugs",
                data: openBug,
                borderColor: "rgba(239, 68, 68, 0.85)",
                backgroundColor: "rgba(239, 68, 68, 0.10)",
                tension: 0.35,
                fill: true,
                pointRadius: 2,
            },
            {
                label: "Open Progress",
                data: openProgress,
                borderColor: "rgba(175, 67, 36, 0.85)",
                backgroundColor: "rgba(175, 67, 36, 0.10)",
                tension: 0.35,
                fill: true,
                pointRadius: 2,
            },
        ],
    };
});

const bugVsFixChart = computed(() => {
    const bug = props.charts?.bug_vs_fix?.bug ?? 0;
    const fix = props.charts?.bug_vs_fix?.fix ?? 0;

    return {
        labels: [logTypeLabel.bug, logTypeLabel.fix],
        datasets: [
            {
                label: "Count",
                data: [bug, fix],
                backgroundColor: ["rgba(239, 68, 68, 0.35)", "rgba(34, 197, 94, 0.35)"],
                borderColor: ["rgba(239, 68, 68, 0.8)", "rgba(34, 197, 94, 0.8)"],
                borderWidth: 1,
            },
        ],
    };
});

const bugSlaOnTimeChart = computed(() => {
    const onTime = props.charts?.bug_sla_on_time?.on_time ?? 0;
    const late = props.charts?.bug_sla_on_time?.late ?? 0;

    return {
        labels: ["On Time", "Late"],
        datasets: [
            {
                data: [onTime, late],
                backgroundColor: ["rgba(34, 197, 94, 0.35)", "rgba(239, 68, 68, 0.35)"],
                borderColor: ["rgba(34, 197, 94, 0.8)", "rgba(239, 68, 68, 0.8)"],
                borderWidth: 1,
            },
        ],
    };
});

const toggleType = (type) => {
    const current = new Set(form.types);
    if (current.has(type)) current.delete(type);
    else current.add(type);
    form.types = Array.from(current);
};

const applyFilters = () => {
    router.get(
        route("reports.index"),
        {
            start_date: form.start_date || null,
            end_date: form.end_date || null,
            system_id: form.system_id || null,
            feature_id: form.feature_id || null,
            types: form.types,
            view_mode: form.view_mode,
            report_view: form.report_view ? 1 : 0,
            progress_status_period: form.progress_status_period ? 1 : 0,
            completion_trend_period: form.completion_trend_period ? 1 : 0,
            backlog_trend_period: form.backlog_trend_period ? 1 : 0,
        },
        { preserveScroll: true, preserveState: true, replace: true }
    );
};

const setViewMode = (mode) => {
    form.view_mode = mode;
    applyFilters();
};

const toggleReportView = () => {
    form.report_view = !form.report_view;
    applyFilters();
};

const formatDate = (iso) => {
    return formatDayDate(iso) || "—";
};

const bugFixMeetingStatus = computed(() =>
    getBugFixMeetingStatus({ kpis: props.kpis, bug_summary: props.bug_summary, charts: props.charts })
);

const progressMeetingStatus = computed(() =>
    getProgressMeetingStatus({ kpis: props.kpis })
);

const operationalInsights = computed(() =>
    getOperationalInsights({ kpis: props.kpis, bug_summary: props.bug_summary, charts: props.charts })
);

const derivedMetrics = computed(() =>
    getDerivedMetrics({ kpis: props.kpis, bug_summary: props.bug_summary, charts: props.charts })
);
</script>

<template>
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xl font-semibold text-slate-900">
                        Laporan
                    </div>
                    <div class="text-sm text-slate-500">
                        Ringkasan KPI, aktivitas, dan export untuk meeting/reporting.
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-[#AF4324]/30 bg-white px-3 py-2 text-sm font-medium text-[#AF4324] hover:bg-[#AF4324]/10 hover:border-[#AF4324]/50 transition disabled:opacity-50"
                        :disabled="exporting"
                        @click="exportSummaryPng"
                        data-export-ignore="true"
                    >
                        Export Summary (PNG)
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-[#AF4324]/30 bg-white px-3 py-2 text-sm font-medium text-[#AF4324] hover:bg-[#AF4324]/10 hover:border-[#AF4324]/50 transition disabled:opacity-50"
                        :disabled="exporting"
                        @click="exportVisualPng"
                        data-export-ignore="true"
                    >
                        Export Visual (PNG)
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-[#AF4324] bg-[#AF4324] px-3 py-2 text-sm font-medium text-white hover:opacity-95 transition disabled:opacity-50"
                        :disabled="exporting"
                        @click="exportPdf"
                        data-export-ignore="true"
                    >
                        Export Report (PDF)
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border px-3 py-2 text-sm font-medium transition"
                        :class="
                            form.report_view
                                ? 'border-[#AF4324] bg-[#AF4324] text-white hover:opacity-95'
                                : 'border-[#AF4324]/30 bg-white text-[#AF4324] hover:bg-[#AF4324]/10 hover:border-[#AF4324]/50'
                        "
                        @click="toggleReportView"
                        title="Toggle report view"
                        data-export-ignore="true"
                    >
                        {{ form.report_view ? "Normal View" : "Report View" }}
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="border border-slate-200 rounded-lg p-5 space-y-4">
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <div class="space-y-1">
                        <div class="text-xs font-medium text-slate-600">
                            Start Date
                        </div>
                        <DateField v-model="form.start_date" />
                    </div>

                    <div class="space-y-1">
                        <div class="text-xs font-medium text-slate-600">
                            End Date
                        </div>
                        <DateField v-model="form.end_date" />
                    </div>

                    <div class="space-y-1">
                        <div class="text-xs font-medium text-slate-600">
                            System
                        </div>
                        <button
                            type="button"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-slate-900 hover:border-slate-300 transition"
                            @click="systemPickerOpen = true"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="truncate">{{ selectedSystemName }}</span>
                                <span class="text-slate-400">⌄</span>
                            </div>
                        </button>
                    </div>

                    <div class="space-y-1">
                        <div class="text-xs font-medium text-slate-600">
                            Feature (optional)
                        </div>
                        <button
                            type="button"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-slate-900 hover:border-slate-300 transition disabled:opacity-60"
                            :disabled="(featureOptions ?? []).length === 0"
                            @click="featurePickerOpen = true"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="truncate">{{ selectedFeatureName }}</span>
                                <span class="text-slate-400">⌄</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div
                    class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-xs font-medium text-slate-600 mr-1">
                            Log Type
                        </div>
                        <label
                            class="inline-flex items-center gap-2 text-sm text-slate-700"
                        >
                            <input
                                type="checkbox"
                                :checked="form.types.includes('progress')"
                                @change="toggleType('progress')"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-200"
                            />
                            {{ logTypeLabel.progress }}
                        </label>
                        <label
                            class="inline-flex items-center gap-2 text-sm text-slate-700"
                        >
                            <input
                                type="checkbox"
                                :checked="form.types.includes('bug')"
                                @change="toggleType('bug')"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-200"
                            />
                            {{ logTypeLabel.bug }}
                        </label>
                        <label
                            class="inline-flex items-center gap-2 text-sm text-slate-700"
                        >
                            <input
                                type="checkbox"
                                :checked="form.types.includes('fix')"
                                @change="toggleType('fix')"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-200"
                            />
                            {{ logTypeLabel.fix }}
                        </label>

                        <button
                            type="button"
                            class="ml-2 inline-flex items-center rounded-lg border border-[#AF4324] bg-[#AF4324] px-3 py-2 text-sm font-medium text-white hover:opacity-95 transition"
                            @click="applyFilters"
                        >
                            Apply
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="text-xs font-medium text-slate-600 mr-1">
                            View Mode
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border px-3 py-2 text-sm font-medium transition"
                            :class="
                                form.view_mode === 'detail'
                                    ? 'border-[#AF4324] bg-[#AF4324] text-white'
                                    : 'border-slate-200 text-slate-700 hover:bg-[#AF4324]/10 hover:border-[#AF4324]/40'
                            "
                            @click="setViewMode('detail')"
                        >
                            Detail
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border px-3 py-2 text-sm font-medium transition"
                            :class="
                                form.view_mode === 'summary'
                                    ? 'border-[#AF4324] bg-[#AF4324] text-white'
                                    : 'border-slate-200 text-slate-700 hover:bg-[#AF4324]/10 hover:border-[#AF4324]/40'
                            "
                            @click="setViewMode('summary')"
                        >
                            Summary
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border px-3 py-2 text-sm font-medium transition"
                            :class="
                                form.view_mode === 'visual'
                                    ? 'border-[#AF4324] bg-[#AF4324] text-white'
                                    : 'border-slate-200 text-slate-700 hover:bg-[#AF4324]/10 hover:border-[#AF4324]/40'
                            "
                            @click="setViewMode('visual')"
                        >
                            Visual
                        </button>
                    </div>
                </div>
            </div>

            <!-- System Picker Modal -->
            <div
                v-if="systemPickerOpen"
                class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
                @click.self="systemPickerOpen = false"
            >
                <div class="w-full max-w-xl rounded-2xl bg-white shadow-lg border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">
                                Pilih System
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Gunakan search untuk cepat menemukan system.
                            </div>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 transition"
                            @click="systemPickerOpen = false"
                        >
                            Tutup
                        </button>
                    </div>

                    <div class="p-5">
                        <input
                            v-model="systemSearch"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                            placeholder="Cari system..."
                        />

                        <div class="mt-4 max-h-[55vh] overflow-auto divide-y border rounded-xl">
                            <button
                                type="button"
                                class="w-full text-left p-4 hover:bg-slate-50 transition"
                                @click="pickSystem('')"
                            >
                                <div class="text-sm font-medium text-slate-900">
                                    Semua System
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Tidak memfilter system.
                                </div>
                            </button>

                            <button
                                v-for="s in systemPickerList"
                                :key="s.id"
                                type="button"
                                class="w-full text-left p-4 hover:bg-slate-50 transition"
                                @click="pickSystem(s.id)"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-slate-900 truncate">
                                            {{ s.name }}
                                        </div>
                                    </div>
                                    <span
                                        v-if="String(form.system_id) === String(s.id)"
                                        class="text-xs font-semibold text-[#AF4324]"
                                    >
                                        Terpilih
                                    </span>
                                </div>
                            </button>

                            <div
                                v-if="(systemPickerList ?? []).length === 0"
                                class="p-6 text-center text-sm text-slate-500"
                            >
                                Tidak ada system yang cocok.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Picker Modal -->
            <div
                v-if="featurePickerOpen"
                class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
                @click.self="featurePickerOpen = false"
            >
                <div class="w-full max-w-xl rounded-2xl bg-white shadow-lg border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">
                                Pilih Feature
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ form.system_id ? `System: ${selectedSystemName}` : "Semua system" }}
                            </div>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 transition"
                            @click="featurePickerOpen = false"
                        >
                            Tutup
                        </button>
                    </div>

                    <div class="p-5">
                        <input
                            v-model="featureSearch"
                            type="text"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                            placeholder="Cari feature..."
                        />

                        <div class="mt-4 max-h-[55vh] overflow-auto divide-y border rounded-xl">
                            <button
                                type="button"
                                class="w-full text-left p-4 hover:bg-slate-50 transition"
                                @click="pickFeature('')"
                            >
                                <div class="text-sm font-medium text-slate-900">
                                    Semua Feature
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Tidak memfilter feature.
                                </div>
                            </button>

                            <button
                                v-for="f in featurePickerList"
                                :key="f.id"
                                type="button"
                                class="w-full text-left p-4 hover:bg-slate-50 transition"
                                @click="pickFeature(f.id)"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-slate-900 truncate">
                                            {{ f.optionLabel }}
                                        </div>
                                    </div>
                                    <span
                                        v-if="String(form.feature_id) === String(f.id)"
                                        class="text-xs font-semibold text-[#AF4324]"
                                    >
                                        Terpilih
                                    </span>
                                </div>
                            </button>

                            <div
                                v-if="(featurePickerList ?? []).length === 0"
                                class="p-6 text-center text-sm text-slate-500"
                            >
                                Tidak ada feature yang cocok.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div ref="reportRef" class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                    <div class="text-sm font-medium text-slate-900">
                        Ringkasan Filter
                    </div>
                    <div class="mt-1 text-sm text-slate-600">
                        {{ filterFeedbackText }}
                    </div>
                </div>

                <!-- KPI cards (always) -->
                <div
                    ref="kpiRef"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4"
                >
                    <StatCard
                        label="Total Logs"
                        :value="kpis?.total_logs ?? 0"
                    />
                    <StatCard
                        label="Bug Logs"
                        :value="kpis?.bug_logs ?? 0"
                        :hint="`Bug open (periode): ${kpis?.open_bug_count ?? 0} · Resolved bug: ${kpis?.resolved_bug_count ?? 0}`"
                    />
                    <StatCard
                        label="Fix Logs"
                        :value="kpis?.fix_logs ?? 0"
                        :hint="`SLA on-time: ${kpis?.bug_sla_on_time_rate ?? 0}%`"
                    />
                    <StatCard
                        label="Completion Rate"
                        :value="`${kpis?.completion_rate ?? 0}%`"
                        :hint="`${kpis?.progress_done ?? 0} Done · ${bug_summary?.resolved_fix ?? 0} Fix resolved`"
                    />
                    <StatCard
                        label="Progress Status"
                        :value="kpis?.progress_logs ?? 0"
                        :hint="`${kpis?.progress_on_progress ?? 0} On Progress, ${kpis?.progress_done ?? 0} Done`"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="text-sm text-slate-700">
                        Fix Logs: <span class="font-medium">{{ kpis?.fix_logs ?? 0 }}</span>
                    </div>
                    <div
                        class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium"
                        :class="slaTone"
                    >
                        SLA On Time: {{ kpis?.bug_sla_on_time_rate ?? 0 }}%
                    </div>
                </div>

            <!-- DETAIL MODE -->
            <div v-if="form.view_mode === 'detail'" class="space-y-6">
                <!-- Log Table -->
                <div class="border border-slate-200 rounded-lg p-5 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm font-semibold text-slate-900">
                            Tabel Log
                        </div>
                        <div class="text-xs text-slate-500">
                            Periode: {{ form.start_date }} → {{ form.end_date }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr
                                    class="text-left text-xs font-semibold text-slate-600 border-b border-slate-200"
                                >
                                    <th class="py-3 pr-4">Date</th>
                                    <th class="py-3 pr-4">System</th>
                                    <th class="py-3 pr-4">Feature</th>
                                    <th class="py-3 pr-4">Type</th>
                                    <th class="py-3 pr-4">Status</th>
                                    <th class="py-3 pr-4">Reference</th>
                                    <th class="py-3 pr-4">SLA</th>
                                    <th class="py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="log in log_table"
                                    :key="log.id"
                                    class="align-top hover:bg-slate-50 cursor-pointer"
                                    @click="router.visit(route('logs.show', log.id))"
                                >
                                    <td class="py-4 pr-4 text-slate-700">
                                        <div class="font-medium">
                                            {{ formatDate(log.logged_at) }}
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4 text-slate-700">
                                        {{ log.system_name ?? "—" }}
                                    </td>
                                    <td class="py-4 pr-4 text-slate-700">
                                        {{ log.feature_title ?? "—" }}
                                    </td>
                                    <td class="py-4 pr-4">
                                        <Badge
                                            :label="
                                                logTypeLabel[log.type] ??
                                                log.type
                                            "
                                            :colorClass="
                                                logTypeMap[log.type] ??
                                                'bg-slate-100 text-slate-700'
                                            "
                                        />
                                    </td>
                                    <td class="py-4 pr-4">
                                        <Badge
                                            v-if="log.status"
                                            :label="
                                                logStatusLabel[log.status] ??
                                                log.status
                                            "
                                            :colorClass="
                                                logStatusMap[log.status] ??
                                                'bg-slate-100 text-slate-700'
                                            "
                                        />
                                        <span v-else class="text-slate-400"
                                            >—</span
                                        >
                                    </td>
                                    <td class="py-4 pr-4 text-slate-700">
                                        <div
                                            v-if="
                                                log.type === 'fix' &&
                                                (log.reference_count ?? 0) > 0
                                            "
                                            class="space-y-1"
                                        >
                                            <div class="text-xs font-medium text-slate-700">
                                                Fix ({{ log.reference_count }} bug resolved)
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                <div
                                                    v-for="r in (log.references ?? []).slice(0, 2)"
                                                    :key="r.id"
                                                    class="truncate"
                                                >
                                                    - Bug #{{ r.id }} — {{ r.title }}
                                                </div>
                                                <div
                                                    v-if="(log.references ?? []).length > 2"
                                                    class="text-slate-400"
                                                >
                                                    +{{ (log.references ?? []).length - 2 }} lainnya
                                                </div>
                                            </div>
                                        </div>
                                        <span v-else class="text-slate-400">—</span>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <span
                                            v-if="log.sla_on_time === true"
                                            class="text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-1"
                                        >
                                            On Time
                                        </span>
                                        <span
                                            v-else-if="log.sla_on_time === false"
                                            class="text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-full px-2 py-1"
                                        >
                                            Late
                                        </span>
                                        <span v-else class="text-slate-400"
                                            >—</span
                                        >
                                    </td>
                                    <td
                                        class="py-4 text-slate-900 whitespace-normal break-words"
                                    >
                                        {{ log.title }}
                                    </td>
                                </tr>

                                <tr
                                    v-if="(log_table ?? []).length === 0"
                                >
                                    <td
                                        colspan="8"
                                        class="py-8 text-center text-sm text-slate-500"
                                    >
                                        Belum ada aktivitas pada periode ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Open Bugs (visibility) -->
                <div class="border border-slate-200 rounded-lg p-5 space-y-3">
                    <div class="text-sm font-semibold text-slate-900">
                        Bug Open (Terbaru)
                    </div>
                    <div v-if="(highlights?.open_bugs ?? []).length === 0" class="text-sm text-slate-500">
                        Tidak ada bug yang tercatat (status: open).
                    </div>
                    <ul v-else class="space-y-2 text-sm">
                        <li
                            v-for="log in highlights?.open_bugs ?? []"
                            :key="log.id"
                            class="rounded-lg border border-slate-200 p-3"
                        >
                            <div class="font-medium text-slate-900 break-words">
                                {{ log.title }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ log.system_name ?? "—" }} · {{ formatDate(log.logged_at) }}
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Optional grouping -->
                <div class="border border-slate-200 rounded-lg p-5 space-y-4">
                    <div class="text-sm font-semibold text-slate-900">
                        Rekap (Grouping)
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="text-xs font-semibold text-slate-700">
                                Per System
                            </div>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li
                                    v-for="(count, name) in grouping?.by_system ?? {}"
                                    :key="name"
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="truncate">{{ name }}</span>
                                    <span class="text-slate-500">{{ count }}</span>
                                </li>
                                <li
                                    v-if="
                                        Object.keys(grouping?.by_system ?? {})
                                            .length === 0
                                    "
                                    class="text-slate-500"
                                >
                                    Belum ada data.
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="text-xs font-semibold text-slate-700">
                                Per Feature
                            </div>
                            <ul class="mt-2 space-y-1 text-sm text-slate-700">
                                <li
                                    v-for="(count, name) in grouping?.by_feature ?? {}"
                                    :key="name"
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="truncate">{{ name }}</span>
                                    <span class="text-slate-500">{{ count }}</span>
                                </li>
                                <li
                                    v-if="
                                        Object.keys(grouping?.by_feature ?? {})
                                            .length === 0
                                    "
                                    class="text-slate-500"
                                >
                                    Belum ada data.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Bug Summary -->
                <div class="border border-slate-200 rounded-lg p-5 space-y-4">
                    <div class="text-sm font-semibold text-slate-900">
                        Ringkasan Bug
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ bug_summary?.total_bug ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">Total Bug</div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ bug_summary?.resolved_fix ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">
                                Resolved (Fix)
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ bug_summary?.on_time ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">On Time</div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ bug_summary?.late ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">Late</div>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="border border-slate-200 rounded-lg p-5 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm font-semibold text-slate-900">
                            Activity Feed
                        </div>
                        <div class="text-xs text-slate-500">
                            Showing {{ (activity ?? []).length }} items
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="log in activity"
                            :key="log.id"
                            class="flex items-start gap-3 rounded-lg border border-slate-200 p-3"
                        >
                            <div class="pt-0.5">
                                <Badge
                                    :label="logTypeLabel[log.type] ?? log.type"
                                    :colorClass="
                                        logTypeMap[log.type] ??
                                        'bg-slate-100 text-slate-700'
                                    "
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="text-sm font-medium text-slate-900 whitespace-normal break-words"
                                >
                                    {{ log.title }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    <span>{{ log.system_name ?? "—" }}</span>
                                    <span v-if="log.feature_title">
                                        · {{ log.feature_title }}
                                    </span>
                                    <span> · {{ formatDate(log.logged_at) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge
                                    v-if="log.impact"
                                    :label="
                                        impactLabel[log.impact] ?? log.impact
                                    "
                                    :colorClass="
                                        impactMap[log.impact] ??
                                        'bg-slate-100 text-slate-700'
                                    "
                                />
                                <Badge
                                    v-if="log.status"
                                    :label="
                                        logStatusLabel[log.status] ??
                                        log.status
                                    "
                                    :colorClass="
                                        logStatusMap[log.status] ??
                                        'bg-slate-100 text-slate-700'
                                    "
                                />
                            </div>
                        </div>

                        <div
                            v-if="(activity ?? []).length === 0"
                            class="text-sm text-slate-500"
                        >
                            Belum ada aktivitas pada periode ini.
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY MODE -->
            <div v-else-if="form.view_mode === 'summary'" class="space-y-6">
                <div
                    class="border rounded-lg p-5"
                    :class="bugFixMeetingStatus.tone"
                >
                    <div class="text-sm font-semibold">Status Ringkas (Bug & Fix)</div>
                    <div class="mt-1 text-sm font-medium">
                        {{ bugFixMeetingStatus.title }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{ bugFixMeetingStatus.text }}
                    </div>
                    <div class="mt-2 text-xs opacity-80">
                        <span class="font-medium">{{ bugFixMeetingStatus.metrics?.bug_logs ?? 0 }}</span>
                        Bug ·
                        <span class="font-medium">{{ bugFixMeetingStatus.metrics?.fix_logs ?? 0 }}</span>
                        Fix ·
                        <span class="font-medium">{{ bugFixMeetingStatus.metrics?.open_bug_count ?? 0 }}</span>
                        Open
                        <span
                            v-if="
                                bugFixMeetingStatus.metrics?.sla_on_time_rate_pct !== null &&
                                bugFixMeetingStatus.metrics?.sla_on_time_rate_pct !== undefined
                            "
                        >
                            · SLA
                            <span class="font-medium">
                                {{ bugFixMeetingStatus.metrics?.sla_on_time_rate_pct ?? 0 }}%
                            </span>
                        </span>
                    </div>
                </div>

                <div
                    class="border rounded-lg p-5"
                    :class="progressMeetingStatus.tone"
                >
                    <div class="text-sm font-semibold">Status Ringkas (Progress)</div>
                    <div class="mt-1 text-sm font-medium">
                        {{ progressMeetingStatus.title }}
                    </div>
                    <div class="mt-1 text-sm">
                        {{ progressMeetingStatus.text }}
                    </div>
                    <div class="mt-2 text-xs opacity-80">
                        <span class="font-medium">{{ progressMeetingStatus.metrics?.progress_logs ?? 0 }}</span>
                        Progress ·
                        <span class="font-medium">{{ progressMeetingStatus.metrics?.progress_on_progress ?? 0 }}</span>
                        On Progress ·
                        <span class="font-medium">{{ progressMeetingStatus.metrics?.progress_done ?? 0 }}</span>
                        Done
                        <span
                            v-if="
                                progressMeetingStatus.metrics?.completion_rate_pct !== null &&
                                progressMeetingStatus.metrics?.completion_rate_pct !== undefined
                            "
                        >
                            · Completion
                            <span class="font-medium">
                                {{ progressMeetingStatus.metrics?.completion_rate_pct ?? 0 }}%
                            </span>
                        </span>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-lg p-5 space-y-4">
                    <div class="text-sm font-semibold text-slate-900">
                        Operational Insights
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div
                            v-for="item in operationalInsights"
                            :key="item.key"
                            class="rounded-lg border p-4"
                            :class="item.tone"
                        >
                            <div class="text-xs font-semibold opacity-80">
                                {{ item.title }}
                            </div>
                            <div class="mt-1 text-sm">
                                {{ item.text }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-lg p-5">
                    <div class="text-sm font-semibold text-slate-900">
                        Operational Metrics
                    </div>
                    <div class="mt-3 grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div
                            v-for="m in derivedMetrics"
                            :key="m.key"
                            class="rounded-lg border border-slate-200 bg-white p-4"
                        >
                            <div class="text-xs font-semibold text-slate-700">
                                {{ m.label }}
                            </div>
                            <div class="mt-1 text-xl font-semibold" :class="m.tone ?? 'text-slate-900'">
                                {{ m.value }}
                            </div>
                            <div v-if="m.hint" class="mt-1 text-xs text-slate-500">
                                {{ m.hint }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISUAL MODE -->
            <div v-else ref="visualRef" class="space-y-6">
                <!-- Row 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <ChartCard
                        title="Bug vs Fix"
                        subtitle="Issue vs resolution ratio pada periode terpilih."
                        type="bar"
                        :data="bugVsFixChart"
                        :options="{
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, ticks: { precision: 0 } },
                            },
                        }"
                        :height="260"
                    />

                    <ChartCard
                        title="Bug SLA On-Time vs Late"
                        subtitle="Fix resolved pada periode terpilih."
                        type="doughnut"
                        :data="bugSlaOnTimeChart"
                        :options="{ cutout: '65%' }"
                        :height="260"
                    />
                </div>

                <!-- Row 3 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <ChartCard
                        title="Progress Status Distribution"
                        subtitle="Kondisi pekerjaan (done vs on progress)."
                        type="doughnut"
                        :data="progressStatusChart"
                        :options="{ cutout: '65%' }"
                        :height="260"
                    >
                        <template #actions>
                            <label
                                class="inline-flex items-center gap-2 text-xs text-slate-600"
                                title="Jika aktif, mengikuti periode filter. Jika tidak, memakai data keseluruhan."
                                data-export-ignore="true"
                            >
                                <input
                                    type="checkbox"
                                    v-model="form.progress_status_period"
                                    class="rounded border-slate-300 text-[#AF4324] focus:ring-[#AF4324]/20"
                                    @change="applyFilters"
                                />
                                Ikuti periode
                            </label>
                        </template>
                    </ChartCard>

                    <ChartCard
                        title="Issue Impact Distribution"
                        subtitle="Distribusi severity bug pada periode terpilih."
                        type="doughnut"
                        :data="impactDistributionChart"
                        :options="{ cutout: '65%' }"
                        :height="260"
                    />
                </div>

                <!-- Row 4 -->
                <ChartCard
                    title="Completion Trend"
                    subtitle="Jumlah pekerjaan selesai per hari (tidak kumulatif)."
                    type="line"
                    :data="completionTrendChart"
                    :options="{
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    }"
                    :height="280"
                >
                    <template #actions>
                        <label
                            class="inline-flex items-center gap-2 text-xs text-slate-600"
                            title="Jika aktif, hanya menghitung completion dari log di periode filter. Jika tidak, tetap per hari dalam rentang tanggal yang dipilih, tetapi memakai data keseluruhan (snapshot)."
                            data-export-ignore="true"
                        >
                            <input
                                type="checkbox"
                                v-model="form.completion_trend_period"
                                class="rounded border-slate-300 text-[#AF4324] focus:ring-[#AF4324]/20"
                                @change="applyFilters"
                            />
                            Ikuti periode
                        </label>
                    </template>
                </ChartCard>

                <!-- Row 5 -->
                <ChartCard
                    title="Open Issue Backlog Trend"
                    subtitle="Perubahan backlog dari waktu ke waktu (kumulatif)."
                    type="line"
                    :data="backlogTrendChart"
                    :options="{
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    }"
                    :height="280"
                >
                    <template #actions>
                        <label
                            class="inline-flex items-center gap-2 text-xs text-slate-600"
                            title="Jika aktif, hanya perubahan backlog pada periode ini (mulai dari 0). Jika tidak, termasuk backlog sebelum periode."
                            data-export-ignore="true"
                        >
                            <input
                                type="checkbox"
                                v-model="form.backlog_trend_period"
                                class="rounded border-slate-300 text-[#AF4324] focus:ring-[#AF4324]/20"
                                @change="applyFilters"
                            />
                            Ikuti periode
                        </label>
                    </template>
                </ChartCard>

                <div
                    v-if="(kpis?.total_logs ?? 0) === 0"
                    class="text-sm text-slate-500"
                >
                    Belum ada aktivitas pada periode ini.
                </div>
            </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
