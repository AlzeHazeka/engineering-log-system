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

const form = reactive({
    start_date: props.filters?.start_date ?? "",
    end_date: props.filters?.end_date ?? "",
    system_id: props.filters?.system_id ?? "",
    feature_id: props.filters?.feature_id ?? "",
    types: props.filters?.types ?? ["progress", "bug", "fix"],
    view_mode: props.filters?.view_mode ?? "detail",
    report_view: !!props.filters?.report_view,
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

const progressTrendChart = computed(() => {
    const labels = props.charts?.progress_trend?.labels ?? [];
    const data = props.charts?.progress_trend?.data ?? [];

    return {
        labels,
        datasets: [
            {
                label: "Progress logs",
                data,
                borderColor: "#AF4324",
                backgroundColor: "rgba(175, 67, 36, 0.15)",
                tension: 0.35,
                fill: true,
                pointRadius: 2,
            },
        ],
    };
});

const activityDayInsight = computed(() => {
    const data = props.charts?.progress_trend?.data ?? [];
    const labels = props.charts?.progress_trend?.labels ?? [];
    if (!labels.length) return "";
    const activeDays = (data || []).filter((v) => Number(v) > 0).length;
    if (activeDays === 0) return "Belum ada aktivitas pada periode ini.";
    if (activeDays === 1) return "Aktivitas hanya terjadi pada 1 hari dalam periode ini.";
    return `Aktivitas terjadi pada ${activeDays} hari dalam periode ini.`;
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

const meetingStatus = computed(() => {
    const bugRate = props.kpis?.bug_sla_on_time_rate ?? 0;
    const openBugs = props.kpis?.open_bug_count ?? 0;
    const bugLogs = props.kpis?.bug_logs ?? 0;
    const fixLogs = props.kpis?.fix_logs ?? 0;
    const slaLate = props.bug_summary?.late ?? 0;

    if (slaLate > 0 || bugRate < 70 || openBugs >= 10) {
        return {
            tone: "bg-red-50 border-red-200 text-red-800",
            text: "🔴 Perlu perhatian pada SLA bug & backlog.",
        };
    }
    if (bugLogs > fixLogs || bugRate < 85 || openBugs > 0) {
        return {
            tone: "bg-yellow-50 border-yellow-200 text-yellow-800",
            text: "🟡 Ada keterlambatan minor yang perlu dipantau.",
        };
    }
    return {
        tone: "bg-green-50 border-green-200 text-green-800",
        text: "🟢 Development berjalan baik.",
    };
});
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
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition disabled:opacity-50"
                        :disabled="exporting"
                        @click="exportSummaryPng"
                        data-export-ignore="true"
                    >
                        Export Summary (PNG)
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition disabled:opacity-50"
                        :disabled="exporting"
                        @click="exportVisualPng"
                        data-export-ignore="true"
                    >
                        Export Visual (PNG)
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition disabled:opacity-50"
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
                                ? 'border-slate-200 text-slate-700 hover:bg-slate-50'
                                : 'border-slate-300 bg-slate-900 text-white'
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
                        <select
                            v-model="form.system_id"
                            class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                            @change="
                                (form.feature_id = ''), applyFilters()
                            "
                        >
                            <option value="">All Systems</option>
                            <option
                                v-for="system in systems"
                                :key="system.id"
                                :value="system.id"
                            >
                                {{ system.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <div class="text-xs font-medium text-slate-600">
                            Feature (optional)
                        </div>
                        <select
                            v-model="form.feature_id"
                            class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                            @change="applyFilters"
                        >
                            <option value="">All Features</option>
                            <option
                                v-for="feature in featureOptions"
                                :key="feature.id"
                                :value="feature.id"
                            >
                                {{ feature.optionLabel }}
                            </option>
                        </select>
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
                            class="ml-2 inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
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
                                    ? 'border-slate-300 bg-slate-900 text-white'
                                    : 'border-slate-200 text-slate-700 hover:bg-slate-50'
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
                                    ? 'border-slate-300 bg-slate-900 text-white'
                                    : 'border-slate-200 text-slate-700 hover:bg-slate-50'
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
                                    ? 'border-slate-300 bg-slate-900 text-white'
                                    : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                            "
                            @click="setViewMode('visual')"
                        >
                            Visual
                        </button>
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
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                >
                    <StatCard
                        label="Total Logs"
                        :value="kpis?.total_logs ?? 0"
                    />
                    <StatCard
                        label="Progress Logs"
                        :value="kpis?.progress_logs ?? 0"
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
                    :class="meetingStatus.tone"
                >
                    <div class="text-sm font-semibold">Status Ringkas</div>
                    <div class="mt-1 text-sm">
                        {{ meetingStatus.text }}
                    </div>
                </div>

                <div class="border border-slate-200 rounded-lg p-5 space-y-4">
                    <div class="text-sm font-semibold text-slate-900">
                        Highlight
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div
                                class="text-xs font-semibold text-slate-700"
                            >
                                Fix Terlambat (SLA)
                            </div>
                            <ul class="mt-2 space-y-2 text-sm">
                                <li
                                    v-for="log in highlights?.late_fixes ?? []"
                                    :key="log.id"
                                    class="text-slate-800"
                                >
                                    <div class="font-medium break-words">
                                        {{ log.title }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        SLA: {{ log.sla_days ?? "—" }}d · Took:
                                        {{ log.duration_days ?? "—" }}d
                                    </div>
                                </li>
                                <li
                                    v-if="
                                        (highlights?.late_fixes ?? [])
                                            .length === 0
                                    "
                                    class="text-slate-500"
                                >
                                    Tidak ada fix yang terlambat SLA.
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <div
                                class="text-xs font-semibold text-slate-700"
                            >
                                Bug Open
                            </div>
                            <ul class="mt-2 space-y-2 text-sm">
                                <li
                                    v-for="log in highlights?.open_bugs ?? []"
                                    :key="log.id"
                                    class="text-slate-800"
                                >
                                    <div class="font-medium break-words">
                                        {{ log.title }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{
                                            log.impact
                                                ? impactLabel[log.impact] ??
                                                  log.impact
                                                : "—"
                                        }}
                                        · {{ log.system_name ?? "—" }}
                                        · {{ formatDate(log.logged_at) }}
                                    </div>
                                </li>
                                <li
                                    v-if="
                                        (highlights?.open_bugs ?? [])
                                            .length === 0
                                    "
                                    class="text-slate-500"
                                >
                                    Tidak ada bug open.
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <div
                                class="text-xs font-semibold text-slate-700"
                            >
                                Fix Terbaru (Resolved)
                            </div>
                            <ul class="mt-2 space-y-2 text-sm">
                                <li
                                    v-for="log in highlights?.recent_fix ?? []"
                                    :key="log.id"
                                    class="text-slate-800"
                                >
                                    <div class="font-medium break-words">
                                        {{ log.title }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Resolved:
                                        {{ formatDate(log.resolved_at) }}
                                        ·
                                        {{
                                            log.sla_on_time === true
                                                ? "On Time"
                                                : log.sla_on_time === false
                                                ? "Late"
                                                : "—"
                                        }}
                                    </div>
                                </li>
                                <li
                                    v-if="
                                        (highlights?.recent_fix ?? [])
                                            .length === 0
                                    "
                                    class="text-slate-500"
                                >
                                    Tidak ada fix yang resolved.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-lg p-5">
                    <div class="text-sm font-semibold text-slate-900">
                        Metrik Kunci
                    </div>
                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ kpis?.total_logs ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">
                                Total Logs
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ kpis?.progress_logs ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">
                                Progress Logs
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ kpis?.bug_logs ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">
                                Bug Logs
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ kpis?.fix_logs ?? 0 }}
                            </div>
                            <div class="text-sm text-slate-500">
                                Fix Logs
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISUAL MODE -->
            <div v-else ref="visualRef" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <ChartCard
                        title="Progress Trend"
                        subtitle="Jumlah log progress per hari."
                        type="line"
                        :data="progressTrendChart"
                        :options="{
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, ticks: { precision: 0 } },
                            },
                        }"
                        :height="260"
                    />

                    <ChartCard
                        title="Bug vs Fix"
                        subtitle="Jumlah log pada periode terpilih."
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
                </div>

                <ChartCard
                    title="Bug SLA On-Time vs Late"
                    subtitle="Fix resolved pada periode terpilih."
                    type="doughnut"
                    :data="bugSlaOnTimeChart"
                    :options="{ cutout: '65%' }"
                    :height="260"
                />

                <div v-if="activityDayInsight" class="text-sm text-slate-600">
                    {{ activityDayInsight }}
                </div>

                <div
                    v-if="(charts?.progress_trend?.labels ?? []).length === 0"
                    class="text-sm text-slate-500"
                >
                    Belum ada aktivitas pada periode ini.
                </div>
            </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
