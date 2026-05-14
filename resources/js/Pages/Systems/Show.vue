<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Badge from "@/Components/Badge.vue";
import Accordion from "@/Components/Accordion.vue";
import ProgressBar from "@/Components/ProgressBar.vue";
import StatCard from "@/Components/StatCard.vue";
import {
    systemStatusMap,
    systemStatusLabel,
    systemStageMap,
    systemStageLabel,
    logTypeMap,
    logTypeLabel,
    impactMap,
    impactLabel,
    logStatusMap,
    logStatusLabel,
    featureStatusMap,
    featureStatusLabel,
} from "@/Utils/enums";
import { computed } from "vue";
import { formatDate, timeAgo } from "@/Utils/datetime";
import { router } from "@inertiajs/vue3";
import { ArrowLeft, Eye, Pencil, Plus, Trash2 } from "lucide-vue-next";

const props = defineProps({
    system: Object,
    globalLogs: Array,
    logsByFeature: Object,
});

const avgProgress = computed(() => {
    const n = Number(props.system.features_avg_progress ?? 0);
    if (Number.isNaN(n)) return 0;
    return Math.max(0, Math.min(100, Math.round(n)));
});

const stageBadge = computed(() => {
    const stage =
        props.system.stage ||
        (props.system.released_at ? "production" : "development");

    return {
        label: systemStageLabel[stage] ?? "Unknown",
        colorClass: systemStageMap[stage] ?? "bg-gray-100 text-gray-700",
    };
});

const featureMap = computed(() => {
    const map = new Map();
    (props.system.features ?? []).forEach((f) => map.set(String(f.id), f));
    return map;
});

const featureLogGroups = computed(() => {
    const groups = [];
    const byId = props.logsByFeature ?? {};

    Object.keys(byId).forEach((featureId) => {
        const feature = featureMap.value.get(String(featureId));
        if (!feature) return;
        groups.push({
            feature,
            logs: byId[featureId] || [],
        });
    });

    // Sort by feature status priority and due date-ish (match backend sort).
    const rank = { in_progress: 1, planned: 2, on_hold: 3, done: 4 };
    groups.sort((a, b) => {
        const ra = rank[a.feature.status] ?? 9;
        const rb = rank[b.feature.status] ?? 9;
        if (ra !== rb) return ra - rb;
        return (a.feature.due_date || "").localeCompare(b.feature.due_date || "");
    });

    return groups;
});

const featureStatusText = (status) =>
    featureStatusLabel[status] ?? status ?? "—";

const impactBadge = (impact) => {
    if (!impact) return null;
    return {
        label: impactLabel[impact] ?? impact,
        colorClass: impactMap[impact] ?? "bg-gray-100 text-gray-700",
    };
};

const statusBadge = (status) => {
    if (!status) return null;
    return {
        label: logStatusLabel[status] ?? status,
        colorClass: logStatusMap[status] ?? "bg-gray-100 text-gray-700",
    };
};

const deleteFeature = (feature) => {
    if (confirm(`Delete feature "${feature.title}"?`)) {
        router.delete(
            route("systems.features.destroy", [props.system.slug, feature.id])
        );
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 space-y-8">
                <!-- SECTION 1: HEADER SYSTEM -->
                <div
                    class="flex flex-col md:flex-row md:items-start md:justify-between gap-4"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h2 class="text-2xl font-semibold text-gray-900">
                                {{ system.name }}
                            </h2>
                            <Badge
                                :label="systemStatusLabel[system.status]"
                                :colorClass="systemStatusMap[system.status]"
                            />
                            <Badge
                                :label="stageBadge.label"
                                :colorClass="stageBadge.colorClass"
                            />
                        </div>

                        <div class="text-sm text-gray-500 flex gap-4 flex-wrap">
                            <div v-if="system.released_at">
                                Released: {{ formatDate(system.released_at) }}
                            </div>
                            <div v-else>Released: —</div>

                            <div v-if="system.repository_url">
                                Repo:
                                <a
                                    :href="system.repository_url"
                                    target="_blank"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ system.repository_url }}
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="system.description"
                            class="text-sm text-gray-600"
                        >
                            {{ system.description }}
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a
                            :href="route('systems.index')"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                            title="Back"
                            aria-label="Back"
                        >
                            <ArrowLeft class="h-5 w-5" />
                        </a>
                        <a
                            :href="route('systems.edit', system.slug)"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                            title="Edit system"
                            aria-label="Edit system"
                        >
                            <Pencil class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <!-- SECTION 2: SUMMARY -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <StatCard
                        label="Total Feature"
                        :value="system.features_count ?? 0"
                    />
                    <StatCard
                        label="Feature Done"
                        :value="system.features_done_count ?? 0"
                    />
                    <StatCard
                        label="In Progress"
                        :value="system.features_in_progress_count ?? 0"
                    />
                    <StatCard label="Progress" :value="avgProgress + '%'" />
                </div>

                <!-- SECTION 3: FEATURES LIST -->
                <div class="space-y-4">
                    <div class="flex items-end justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Features
                            </h3>
                            <p class="text-sm text-gray-500">
                                Progress & status per feature.
                            </p>
                        </div>

                        <a
                            :href="
                                route(
                                    'systems.features.create',
                                    system.slug
                                )
                            "
                            class="inline-flex items-center justify-center rounded-lg bg-black text-white p-2 hover:opacity-90 transition"
                            title="Add feature"
                            aria-label="Add feature"
                        >
                            <Plus class="h-5 w-5" />
                        </a>
                    </div>

                    <div
                        class="bg-white rounded-xl shadow-sm border overflow-hidden"
                    >
                        <div
                            v-if="(system.features ?? []).length === 0"
                            class="p-6 text-gray-400 text-center"
                        >
                            No features yet.
                        </div>

                        <div v-else class="divide-y">
                            <div
                                v-for="feature in system.features"
                                :key="feature.id"
                                class="p-5 hover:bg-gray-50 transition"
                            >
                                <div
                                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex items-center gap-2 flex-wrap"
                                        >
                                            <div
                                                class="font-semibold text-gray-900 truncate"
                                            >
                                                {{ feature.title }}
                                            </div>
                                            <Badge
                                                :label="
                                                    featureStatusLabel[
                                                        feature.status
                                                    ] ?? feature.status
                                                "
                                                :colorClass="
                                                    featureStatusMap[
                                                        feature.status
                                                    ] ??
                                                    'bg-gray-100 text-gray-700'
                                                "
                                            />
                                        </div>

                                        <div class="text-sm text-gray-500 mt-1">
                                            Team:
                                            {{
                                                feature.assigned_team || "—"
                                            }}
                                            <span
                                                v-if="feature.due_date"
                                                class="ml-3"
                                            >
                                                Due:
                                                {{ formatDate(feature.due_date) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="w-full md:w-72">
                                        <ProgressBar
                                            :value="feature.progress ?? 0"
                                        />
                                    </div>

                                    <div class="flex items-center gap-3 md:justify-end">
                                        <a
                                            :href="
                                                route(
                                                    'systems.features.edit',
                                                    [
                                                        system.slug,
                                                        feature.id,
                                                    ]
                                                )
                                            "
                                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition"
                                            title="Edit feature"
                                            aria-label="Edit feature"
                                        >
                                            <Pencil class="h-5 w-5" />
                                        </a>

                                        <button
                                            @click="deleteFeature(feature)"
                                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-red-50 hover:text-red-700 transition"
                                            title="Delete feature"
                                            aria-label="Delete feature"
                                        >
                                            <Trash2 class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: GLOBAL LOGS -->
                <div class="space-y-4">
                    <div class="flex items-end justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Global Logs
                            </h3>
                            <p class="text-sm text-gray-500">
                                Logs without feature grouping.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                :href="route('logs.create', { system_id: system.id })"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                                title="Tambah log"
                                aria-label="Tambah log"
                            >
                                <Plus class="h-5 w-5" />
                            </a>
                            <a
                                :href="route('logs.index', { system_id: system.id })"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                                title="Lihat semua log"
                                aria-label="Lihat semua log"
                            >
                                <Eye class="h-5 w-5" />
                            </a>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl shadow-sm border overflow-hidden"
                    >
                        <div
                            v-if="(globalLogs ?? []).length === 0"
                            class="p-6 text-gray-400 text-center"
                        >
                            No global logs.
                        </div>

                        <div v-else class="divide-y">
                            <a
                                v-for="log in globalLogs"
                                :key="log.id"
                                :href="route('logs.show', log.id)"
                                class="block p-5 hover:bg-gray-50 transition"
                            >
                                <div
                                    class="flex flex-col md:flex-row md:items-start md:justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="font-semibold text-gray-900 truncate"
                                        >
                                            {{ log.title }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ timeAgo(log.logged_at) }}
                                        </div>
                                    </div>

                                    <div class="flex gap-2 flex-wrap">
                                        <Badge
                                            :label="logTypeLabel[log.type]"
                                            :colorClass="logTypeMap[log.type]"
                                        />

                                        <Badge
                                            v-if="impactBadge(log.impact)"
                                            :label="impactBadge(log.impact).label"
                                            :colorClass="
                                                impactBadge(log.impact).colorClass
                                            "
                                        />

                                        <Badge
                                            v-if="statusBadge(log.status)"
                                            :label="statusBadge(log.status).label"
                                            :colorClass="
                                                statusBadge(log.status).colorClass
                                            "
                                        />
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- SECTION 5: LOGS BY FEATURE -->
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Logs by Feature
                        </h3>
                        <p class="text-sm text-gray-500">
                            Grouped timeline per feature (collapsed by default).
                        </p>
                    </div>

                    <div v-if="featureLogGroups.length === 0" class="text-sm">
                        <div
                            class="bg-white rounded-xl shadow-sm border p-6 text-gray-400 text-center"
                        >
                            No feature logs yet.
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <Accordion
                            v-for="group in featureLogGroups"
                            :key="group.feature.id"
                            :title="group.feature.title"
                            :subtitle="`${group.logs.length} logs • ${featureStatusText(group.feature.status)} • ${group.feature.progress ?? 0}%`"
                        >
                            <div class="divide-y rounded-lg border bg-white">
                                <a
                                    v-for="log in group.logs"
                                    :key="log.id"
                                    :href="route('logs.show', log.id)"
                                    class="block p-4 hover:bg-gray-50 transition"
                                >
                                    <div
                                        class="flex flex-col md:flex-row md:items-start md:justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="font-medium text-gray-900 truncate"
                                            >
                                                {{ log.title }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 mt-1"
                                            >
                                                {{ timeAgo(log.logged_at) }}
                                            </div>
                                        </div>

                                        <div class="flex gap-2 flex-wrap">
                                            <Badge
                                                :label="
                                                    logTypeLabel[log.type]
                                                "
                                                :colorClass="
                                                    logTypeMap[log.type]
                                                "
                                            />

                                            <Badge
                                                v-if="impactBadge(log.impact)"
                                                :label="
                                                    impactBadge(log.impact).label
                                                "
                                                :colorClass="
                                                    impactBadge(log.impact)
                                                        .colorClass
                                                "
                                            />

                                            <Badge
                                                v-if="statusBadge(log.status)"
                                                :label="
                                                    statusBadge(log.status).label
                                                "
                                                :colorClass="
                                                    statusBadge(log.status)
                                                        .colorClass
                                                "
                                            />
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </Accordion>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
