<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch, onBeforeUnmount } from "vue";
import { Plus, Search, X, RotateCcw } from "lucide-vue-next";
import { useSoftRevalidate } from "@/Utils/softRevalidate";

import LogTable from "@/Components/Logs/LogTable.vue";
import Modal from "@/Components/Modal.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";

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
    system: { type: Object, default: null },
    systems: { type: Array, default: () => [] },
    features: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const currentUrl = computed(() => page.url?.value ?? page.url ?? "");

useSoftRevalidate({ only: ["logs", "systems", "features", "filters", "system"] });

const filters = reactive({
    search: props.filters?.search ?? "",
    type: props.filters?.type ?? "",
    status: props.filters?.status ?? "",
    impact: props.filters?.impact ?? "",
    system_id: props.filters?.system_id ?? "",
    feature_id: props.filters?.feature_id ?? "",
    date_range: props.filters?.date_range ?? "all",
});

const dateRangeOptions = [
    { value: "all", label: "All" },
    { value: "today", label: "Today" },
    { value: "this_week", label: "This Week" },
    { value: "this_month", label: "This Month" },
    { value: "last_30_days", label: "Last 30 Days" },
];

const featureOptions = computed(() => {
    const systemId = String(filters.system_id || "");
    if (!systemId) return props.features ?? [];
    return (props.features ?? []).filter(
        (f) => String(f.system_id) === systemId
    );
});

const hasActiveFilters = computed(() => {
    return (
        String(filters.search || "").trim() !== "" ||
        !!filters.type ||
        !!filters.status ||
        !!filters.impact ||
        !!filters.system_id ||
        !!filters.feature_id ||
        (filters.date_range && filters.date_range !== "all")
    );
});

const toQuery = (overrides = {}) => {
    const q = { ...filters, ...overrides };

    if (typeof q.search === "string") q.search = q.search.trim();

    Object.keys(q).forEach((k) => {
        if (q[k] === "" || q[k] === null || q[k] === undefined) delete q[k];
    });
    if (q.date_range === "all") delete q.date_range;

    return q;
};

const applyFilters = (overrides = {}, options = {}) => {
    router.get(route("logs.index"), toQuery(overrides), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        ...options,
    });
};

let suppressAutoApply = false;

const clearFilter = (key) => {
    suppressAutoApply = true;
    if (key === "date_range") {
        filters.date_range = "all";
    } else if (key === "system_id") {
        filters.system_id = "";
        filters.feature_id = "";
    } else {
        filters[key] = "";
    }
    suppressAutoApply = false;
    applyFilters();
};

const resetFilters = () => {
    suppressAutoApply = true;
    filters.search = "";
    filters.type = "";
    filters.status = "";
    filters.impact = "";
    filters.system_id = "";
    filters.feature_id = "";
    filters.date_range = "all";
    suppressAutoApply = false;
    applyFilters();
};

// Debounced search
let searchTimer = null;
watch(
    () => filters.search,
    () => {
        if (suppressAutoApply) return;
        if (searchTimer) window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            applyFilters();
        }, 400);
    }
);

watch(
    () => filters.system_id,
    () => {
        if (suppressAutoApply) return;
        // If system changes, clear feature when it no longer matches.
        if (filters.feature_id) {
            const validFeature = featureOptions.value.some(
                (f) => String(f.id) === String(filters.feature_id || "")
            );
            if (!validFeature) filters.feature_id = "";
        }
        applyFilters();
    }
);

// Immediate apply for dropdown filters (except system, handled above)
watch(
    () => [filters.type, filters.status, filters.impact, filters.feature_id, filters.date_range],
    () => {
        if (suppressAutoApply) return;
        applyFilters();
    }
);

onBeforeUnmount(() => {
    if (searchTimer) window.clearTimeout(searchTimer);
});

const deleteConfirmOpen = ref(false);
const deleteTargetId = ref(null);

const openDelete = (id) => {
    deleteTargetId.value = id;
    deleteConfirmOpen.value = true;
};

const confirmDelete = () => {
    const id = deleteTargetId.value;
    if (!id) return;
    const url = currentUrl.value
        ? `${route("logs.destroy", id)}?return=${encodeURIComponent(
              currentUrl.value
          )}`
        : route("logs.destroy", id);

    router.delete(url, {
        preserveScroll: true,
        onFinish: () => {
            deleteConfirmOpen.value = false;
            deleteTargetId.value = null;
        },
    });
};

const markDoneModalOpen = ref(false);
const markDoneTarget = ref(null);

const openMarkDone = (log) => {
    markDoneTarget.value = log;
    markDoneModalOpen.value = true;
};

const confirmMarkDone = () => {
    const log = markDoneTarget.value;
    if (!log) return;
    const url = currentUrl.value
        ? `${route("logs.markDone", log.id)}?return=${encodeURIComponent(
              currentUrl.value
          )}`
        : route("logs.markDone", log.id);

    router.put(url, {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            markDoneModalOpen.value = false;
            markDoneTarget.value = null;
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
            >
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
                    :href="
                        currentUrl
                            ? `${route('logs.create')}?return=${encodeURIComponent(currentUrl)}`
                            : route('logs.create')
                    "
                    class="self-start sm:self-auto inline-flex items-center justify-center rounded-lg bg-black text-white p-2 hover:opacity-90 transition"
                    title="Create log"
                    aria-label="Create log"
                >
                    <Plus class="h-5 w-5" />
                </a>
            </div>

            <!-- Filters toolbar -->
            <div class="bg-white rounded-2xl shadow border p-4 space-y-4">
                <div
                    class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start"
                >
                    <!-- Search -->
                    <div class="md:col-span-4">
                        <label class="text-xs text-slate-500">Search</label>
                        <div class="relative mt-1">
                            <Search
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                            />
                            <input
                                v-model="filters.search"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 pl-9 pr-4 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                                placeholder="Search logs..."
                            />
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">Type</label>
                        <select
                            v-model="filters.type"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                        >
                            <option value="">All Types</option>
                            <option
                                v-for="(label, key) in logTypeLabel"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">Status</label>
                        <select
                            v-model="filters.status"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                        >
                            <option value="">All Status</option>
                            <option
                                v-for="(label, key) in logStatusLabel"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">Impact</label>
                        <select
                            v-model="filters.impact"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                        >
                            <option value="">All Impact</option>
                            <option
                                v-for="(label, key) in impactLabel"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">System</label>
                        <select
                            v-model="filters.system_id"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                        >
                            <option value="">All Systems</option>
                            <option
                                v-for="s in systems"
                                :key="s.id"
                                :value="String(s.id)"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">Feature</label>
                        <select
                            v-model="filters.feature_id"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                        >
                            <option value="">All Features</option>
                            <option
                                v-for="f in featureOptions"
                                :key="f.id"
                                :value="String(f.id)"
                            >
                                {{ f.title
                                }}{{
                                    !filters.system_id && f.system_name
                                        ? " (" + f.system_name + ")"
                                        : ""
                                }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">Date Range</label>
                        <select
                            v-model="filters.date_range"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20"
                        >
                            <option
                                v-for="opt in dateRangeOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>
                <p class="text-xs text-slate-500">
                    Search by title, feature, system, or keyword.
                </p>

                <!-- Active filter chips -->
                <div
                    class="flex flex-wrap items-center gap-2"
                    v-if="hasActiveFilters"
                >
                    <div class="text-xs text-slate-500 mr-1">
                        Active Filters:
                    </div>

                    <button
                        v-if="String(filters.search || '').trim() !== ''"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-[#AF4324]/20 bg-[#AF4324]/10 px-3 py-1 text-xs text-[#AF4324] hover:bg-[#AF4324]/15 transition"
                        @click="clearFilter('search')"
                    >
                        Search: {{ filters.search }}
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <button
                        v-if="filters.type"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700 hover:bg-slate-100 transition"
                        @click="clearFilter('type')"
                    >
                        Type: {{ logTypeLabel[filters.type] }}
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <button
                        v-if="filters.status"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700 hover:bg-slate-100 transition"
                        @click="clearFilter('status')"
                    >
                        Status: {{ logStatusLabel[filters.status] }}
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <button
                        v-if="filters.impact"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700 hover:bg-slate-100 transition"
                        @click="clearFilter('impact')"
                    >
                        Impact: {{ impactLabel[filters.impact] }}
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <button
                        v-if="filters.system_id"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700 hover:bg-slate-100 transition"
                        @click="clearFilter('system_id')"
                    >
                        System:
                        {{
                            systems?.find(
                                (s) =>
                                    String(s.id) === String(filters.system_id)
                            )?.name ?? "—"
                        }}
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <button
                        v-if="filters.feature_id"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700 hover:bg-slate-100 transition"
                        @click="clearFilter('feature_id')"
                    >
                        Feature:
                        {{
                            features?.find(
                                (f) =>
                                    String(f.id) === String(filters.feature_id)
                            )?.title ?? "—"
                        }}
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <button
                        v-if="filters.date_range && filters.date_range !== 'all'"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700 hover:bg-slate-100 transition"
                        @click="clearFilter('date_range')"
                    >
                        Date:
                        {{
                            dateRangeOptions.find(
                                (o) => o.value === filters.date_range
                            )?.label ?? filters.date_range
                        }}
                        <X class="h-3.5 w-3.5" />
                    </button>

                    <button
                        type="button"
                        class="ml-auto inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition"
                        @click="resetFilters"
                        title="Reset filters"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Reset
                    </button>
                </div>
            </div>

            <LogTable
                :logs="logs"
                :logTypeMap="logTypeMap"
                :logTypeLabel="logTypeLabel"
                :impactMap="impactMap"
                :impactLabel="impactLabel"
                :logStatusMap="logStatusMap"
                :logStatusLabel="logStatusLabel"
                @delete="openDelete"
                @mark-done="openMarkDone"
            >
                <template #empty>
                    <div class="p-10 text-center">
                        <div class="text-sm font-medium text-slate-800">
                            No logs found for current filters.
                        </div>
                        <div class="mt-1 text-sm text-slate-500">
                            Try changing the search keyword or clearing filters.
                        </div>
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="mt-4 inline-flex items-center rounded-lg border border-[#AF4324] bg-[#AF4324] px-4 py-2 text-sm font-medium text-white hover:opacity-95 transition"
                            @click="resetFilters"
                        >
                            Clear filters
                        </button>
                    </div>
                </template>
            </LogTable>

            <ConfirmModal
                :show="deleteConfirmOpen"
                title="Hapus log?"
                description="Tindakan ini tidak bisa dibatalkan."
                confirmText="Ya, hapus"
                cancelText="Batal"
                tone="danger"
                @close="deleteConfirmOpen = false"
                @confirm="confirmDelete"
            />

            <Modal
                :show="markDoneModalOpen"
                maxWidth="lg"
                @close="markDoneModalOpen = false"
            >
                <div class="p-6">
                    <div class="text-lg font-semibold text-slate-900">
                        Tandai log sebagai Done?
                    </div>
                    <div class="mt-2 text-sm text-slate-600">
                        Ini akan mengubah status progress menjadi <span class="font-medium">Done</span>.
                    </div>

                    <div
                        v-if="markDoneTarget"
                        class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
                    >
                        <div class="text-sm font-medium text-slate-900">
                            {{ markDoneTarget.title }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ markDoneTarget.system?.name ?? "—" }}
                            <span v-if="markDoneTarget.feature">
                                · {{ markDoneTarget.feature.title }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                            @click="markDoneModalOpen = false"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-[#AF4324] bg-[#AF4324] px-4 py-2 text-sm font-medium text-white hover:opacity-95 transition"
                            @click="confirmMarkDone"
                        >
                            Ya, Done
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
