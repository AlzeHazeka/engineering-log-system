<script setup>
import { computed, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import DateTimeField from "@/Components/DateTimeField.vue";
import {
    logTypeLabel,
    logTypeMap,
    impactLabel,
    logStatusLabel,
    logStatusMap,
} from "@/Utils/enums";

const page = usePage();

const props = defineProps({
    systems: Array,
    referenceLogs: Array,
    selectedReferenceIds: { type: Array, default: () => [] },
    log: { type: Object, default: null },
    submitUrl: String,
    method: { type: String, default: "post" }, // post | put
    returnUrl: { type: String, default: null },
});

const isEdit = computed(() => !!props.log);

const pad2 = (n) => String(n).padStart(2, "0");

const formatDateTimeForBackend = (date) => {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return null;
    const y = date.getFullYear();
    const m = pad2(date.getMonth() + 1);
    const d = pad2(date.getDate());
    const hh = pad2(date.getHours());
    const mm = pad2(date.getMinutes());
    const ss = pad2(date.getSeconds());
    return `${y}-${m}-${d} ${hh}:${mm}:${ss}`;
};

const normalizeDateTimeValue = (value) => {
    if (!value) return null;
    if (typeof value !== "string") return null;

    // Already in backend-friendly format: YYYY-MM-DD HH:mm(:ss)
    if (/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}(:\d{2})?$/.test(value)) {
        return value.length === 16 ? `${value}:00` : value;
    }

    // ISO string from backend (e.g. 2026-05-14T10:00:00.000000Z)
    const d = new Date(value);
    if (!Number.isNaN(d.getTime())) return formatDateTimeForBackend(d);

    return null;
};

const bugResolutionSlaDays = computed(
    () => page.props.sla?.bug_resolution_days ?? {}
);

const typeConfig = computed(() => {
    switch (form.type) {
        case "progress":
            return {
                showImpact: false,
                showStatus: true,
                showReference: false,
                helper:
                    "Gunakan untuk update aktivitas. Status optional: On Progress / Done.",
            };
        case "bug":
            return {
                showImpact: true,
                showStatus: true,
                showReference: false,
                helper: "Gunakan untuk mencatat masalah/bug.",
            };
        case "fix":
            return {
                showImpact: false,
                showStatus: true,
                showReference: true,
                helper: "Hubungkan dengan bug yang diselesaikan.",
            };
        case "deployment":
            return {
                showImpact: false,
                showStatus: false,
                showReference: false,
                helper: "Gunakan untuk catatan deployment/release.",
            };
        case "maintenance":
            return {
                showImpact: false,
                showStatus: false,
                showReference: false,
                helper: "Gunakan untuk maintenance. Isi detail di deskripsi.",
            };
        case "decision":
            return {
                showImpact: false,
                showStatus: false,
                showReference: false,
                helper: "Gunakan untuk hasil meeting/keputusan.",
            };
        case "idea":
            return {
                showImpact: false,
                showStatus: false,
                showReference: false,
                helper: "Gunakan untuk ide/hipotesis yang ingin dicatat.",
            };
        default:
            return {
                showImpact: false,
                showStatus: false,
                showReference: false,
                helper: "",
            };
    }
});

const statusRequired = computed(() =>
    ["bug", "fix"].includes(String(form.type))
);

const referenceLocked = computed(() => {
    // UX safeguard: when editing an already-resolved fix that has references,
    // don't allow clearing/unlinking (prevents breaking bug↔fix relationship).
    const originallyFixResolved =
        props.log?.type === "fix" && props.log?.status === "resolved";
    const hasInitialRefs = (props.selectedReferenceIds || []).length > 0;
    return (
        isEdit.value &&
        originallyFixResolved &&
        hasInitialRefs &&
        form.type === "fix"
    );
});

const form = useForm({
    system_id: props.log?.system_id || "",
    feature_id: props.log?.feature_id || "",
    reference_ids: [...(props.selectedReferenceIds || [])],
    type: props.log?.type || "progress",
    impact: props.log?.impact ?? null,
    status: props.log?.status ?? null,
    title: props.log?.title || "",
    description: props.log?.description || "",
    logged_at:
        normalizeDateTimeValue(props.log?.logged_at) ||
        formatDateTimeForBackend(new Date()),
});

const selectedSystem = computed(() =>
    props.systems.find((s) => s.id == form.system_id)
);

const availableFeatures = computed(() => {
    if (!selectedSystem.value) return [];
    return selectedSystem.value.features || [];
});

const availableReferenceLogs = computed(() => {
    if (!form.system_id) return props.referenceLogs || [];
    return (props.referenceLogs || []).filter(
        (l) => l.system_id == form.system_id
    );
});

const statusOptions = computed(() => {
    if (form.type === "bug") {
        return [
            { value: "open", label: logStatusLabel.open },
            { value: "resolved", label: logStatusLabel.resolved },
            { value: "ignored", label: logStatusLabel.ignored },
        ];
    }

    if (form.type === "fix") {
        return [
            { value: "open", label: logStatusLabel.open },
            { value: "resolved", label: logStatusLabel.resolved },
        ];
    }

    if (form.type === "progress") {
        return [
            { value: "on_progress", label: logStatusLabel.on_progress },
            { value: "done", label: logStatusLabel.done },
        ];
    }

    return [];
});

const isValidStatus = (value) =>
    statusOptions.value.some((opt) => opt.value === value);

watch(
    () => form.system_id,
    () => {
        // Reset dependent fields when system changes.
        form.feature_id = "";
        form.reference_ids = [];
    }
);

watch(
    () => form.feature_id,
    () => {
        form.reference_ids = [];
    }
);

watch(
    () => form.type,
    (type) => {
        // Reset irrelevant fields to avoid stale data being submitted.
        if (type === "bug") {
            if (!form.status || !isValidStatus(form.status)) form.status = "open";
        } else if (type === "fix") {
            if (!form.status || !isValidStatus(form.status))
                form.status = "resolved";
        } else if (type === "progress") {
            if (!form.status || !isValidStatus(form.status))
                form.status = "on_progress";
        } else {
            form.status = null;
        }

        if (type !== "bug") {
            form.impact = null;
        }

        if (type !== "fix") {
            form.reference_ids = [];
            referenceModalOpen.value = false;
        }
    }
);

const referenceModalOpen = ref(false);
const referenceSearch = ref("");

const systemPickerOpen = ref(false);
const featurePickerOpen = ref(false);
const systemSearch = ref("");
const featureSearch = ref("");

const systemPickerList = computed(() => {
    const q = systemSearch.value.trim().toLowerCase();
    const list = props.systems ?? [];
    if (!q) return list;
    return list.filter((s) => String(s.name || "").toLowerCase().includes(q));
});

const featurePickerList = computed(() => {
    if (!selectedSystem.value) return [];
    let list = selectedSystem.value.features || [];
    const q = featureSearch.value.trim().toLowerCase();
    if (q) {
        list = list.filter((f) =>
            String(f.title || "").toLowerCase().includes(q)
        );
    }
    return list;
});

const selectedFeature = computed(() => {
    if (!selectedSystem.value || !form.feature_id) return null;
    return (selectedSystem.value.features || []).find(
        (f) => String(f.id) === String(form.feature_id)
    );
});

const pickSystem = (id) => {
    form.system_id = id ? String(id) : "";
    systemPickerOpen.value = false;
};

const pickFeature = (id) => {
    form.feature_id = id ? String(id) : "";
    featurePickerOpen.value = false;
};

const referenceCandidates = computed(() => {
    if (!form.system_id) return [];

    let candidates = availableReferenceLogs.value;

    // Filter by feature (only open bugs)
    if (form.feature_id) {
        candidates = candidates.filter(
            (l) =>
                String(l.feature_id || "") === String(form.feature_id) &&
                l.type === "bug" &&
                l.status === "open"
        );
    }

    // For fix logs, only allow selecting open bugs (even for global logs)
    if (form.type === "fix") {
        candidates = candidates.filter(
            (l) => l.type === "bug" && l.status === "open"
        );
    }

    const q = referenceSearch.value.trim().toLowerCase();
    if (q) {
        candidates = candidates.filter((l) =>
            String(l.title || "").toLowerCase().includes(q)
        );
    }

    return candidates;
});

const isSelectedRef = (id) =>
    (form.reference_ids || []).some((x) => String(x) === String(id));

const toggleReference = (id) => {
    if (referenceLocked.value) return;
    const current = [...(form.reference_ids || [])];
    const idx = current.findIndex((x) => String(x) === String(id));
    if (idx >= 0) {
        current.splice(idx, 1);
    } else {
        current.push(id);
    }
    form.reference_ids = current;
};

const selectedReferenceLogs = computed(() => {
    const ids = new Set((form.reference_ids || []).map((x) => String(x)));
    return (availableReferenceLogs.value || []).filter((l) =>
        ids.has(String(l.id))
    );
});

const clearReferences = () => {
    if (referenceLocked.value) return;
    form.reference_ids = [];
};

watch(
    () => referenceModalOpen.value,
    (open) => {
        if (open) {
            referenceSearch.value = "";
        }
    }
);

watch(
    () => systemPickerOpen.value,
    (open) => {
        if (open) systemSearch.value = "";
    }
);

watch(
    () => featurePickerOpen.value,
    (open) => {
        if (open) featureSearch.value = "";
    }
);

const submit = () => {
    if (props.method === "put") {
        form.put(props.submitUrl);
        return;
    }

    form.post(props.submitUrl);
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border p-4 sm:p-6 space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">
                {{ isEdit ? "Edit Log" : "Create Log" }}
            </h2>
            <p class="text-sm text-gray-500">
                Timeline entry for system/feature.
            </p>
        </div>

        <!-- System -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">System</label>
            <button
                type="button"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20 disabled:opacity-50"
                @click="systemPickerOpen = true"
            >
                <span v-if="selectedSystem" class="text-slate-900">
                    {{ selectedSystem.name }}
                </span>
                <span v-else class="text-slate-400">Pilih system</span>
            </button>
            <div v-if="form.errors.system_id" class="text-sm text-red-600 mt-1">
                {{ form.errors.system_id }}
            </div>
        </div>

        <!-- Feature + Reference -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm text-gray-500"
                        >Feature (optional)</label
                    >
                    <span
                        class="text-xs text-gray-500"
                        :class="form.feature_id ? 'text-blue-700' : ''"
                    >
                        {{ form.feature_id ? "Feature Log" : "Global Log" }}
                    </span>
                </div>
                <button
                    type="button"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm focus:border-[#AF4324] focus:ring-[#AF4324]/20 disabled:opacity-50"
                    :disabled="!form.system_id"
                    @click="featurePickerOpen = true"
                >
                    <span v-if="!form.system_id" class="text-slate-400"
                        >Pilih system dulu</span
                    >
                    <span v-else-if="selectedFeature" class="text-slate-900">
                        {{ selectedFeature.title }}
                    </span>
                    <span v-else class="text-slate-400">Global (tanpa feature)</span>
                </button>
                <div
                    v-if="form.errors.feature_id"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.feature_id }}
                </div>
            </div>

            <div v-if="typeConfig.showReference">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm text-gray-500"
                        >Reference Logs <span class="text-red-600">*</span></label
                    >
                    <span
                        v-if="referenceLocked"
                        class="text-xs text-amber-700"
                    >
                        Terkunci (fix resolved)
                    </span>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="w-full border rounded-lg px-4 py-2 text-left bg-white disabled:opacity-50"
                        :disabled="!form.system_id"
                        @click="referenceModalOpen = true"
                    >
                        <span v-if="(form.reference_ids || []).length === 0"
                            >Pilih reference log</span
                        >
                        <span v-else>
                            {{ (form.reference_ids || []).length }} terpilih
                        </span>
                    </button>

                    <button
                        v-if="(form.reference_ids || []).length > 0"
                        type="button"
                        class="px-3 py-2 rounded-lg border text-sm"
                        @click="clearReferences"
                        :disabled="referenceLocked"
                    >
                        Clear
                    </button>
                </div>

                <div v-if="form.errors.reference_ids" class="text-sm text-red-600 mt-1">
                    {{ form.errors.reference_ids }}
                </div>

                <div class="text-xs text-gray-500 mt-1">
                    Hubungkan fix dengan bug yang diselesaikan (bisa pilih lebih dari 1).
                </div>

                <div
                    v-if="selectedReferenceLogs.length > 0"
                    class="mt-2 text-xs text-gray-500 space-y-1"
                >
                    <div
                        v-for="r in selectedReferenceLogs"
                        :key="r.id"
                        class="truncate"
                    >
                        - {{ r.title }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Type / Impact / Status -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Type</label>
                <select
                    v-model="form.type"
                    class="w-full border rounded-lg px-4 py-2"
                >
                    <option value="progress">{{ logTypeLabel.progress }}</option>
                    <option value="bug">{{ logTypeLabel.bug }}</option>
                    <option value="fix">{{ logTypeLabel.fix }}</option>
                    <option value="deployment">
                        {{ logTypeLabel.deployment }}
                    </option>
                    <option value="maintenance">
                        {{ logTypeLabel.maintenance }}
                    </option>
                    <option value="decision">{{ logTypeLabel.decision }}</option>
                    <option value="idea">{{ logTypeLabel.idea }}</option>
                </select>
                <div v-if="form.errors.type" class="text-sm text-red-600 mt-1">
                    {{ form.errors.type }}
                </div>
                <div v-if="typeConfig.helper" class="text-xs text-gray-500 mt-1">
                    {{ typeConfig.helper }}
                </div>
            </div>

            <div v-if="typeConfig.showImpact">
                <label class="block text-sm text-gray-500 mb-1"
                    >Impact <span class="text-red-600">*</span></label
                >
                <select
                    v-model="form.impact"
                    class="w-full border rounded-lg px-4 py-2"
                    required
                >
                    <option value="">Pilih impact</option>
                    <option value="low">{{ impactLabel.low }}</option>
                    <option value="medium">{{ impactLabel.medium }}</option>
                    <option value="high">{{ impactLabel.high }}</option>
                    <option value="critical">{{ impactLabel.critical }}</option>
                </select>
                <div
                    v-if="form.errors.impact"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.impact }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    Expected resolution (SLA): Low {{ bugResolutionSlaDays.low }}d ·
                    Medium {{ bugResolutionSlaDays.medium }}d ·
                    High {{ bugResolutionSlaDays.high }}d ·
                    Critical {{ bugResolutionSlaDays.critical }}d
                </div>
            </div>

            <div v-if="typeConfig.showStatus">
                <label class="block text-sm text-gray-500 mb-1"
                    >Status
                    <span v-if="statusRequired" class="text-red-600">*</span></label
                >
                <select
                    v-model="form.status"
                    class="w-full border rounded-lg px-4 py-2"
                    :disabled="statusOptions.length === 0"
                >
                    <option value="">Pilih status</option>
                    <option
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>
                <div
                    v-if="form.errors.status"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.status }}
                </div>
            </div>
        </div>

        <!-- Title -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">Title</label>
            <input
                v-model="form.title"
                class="w-full border rounded-lg px-4 py-2"
                placeholder="e.g. Checkout gagal"
            />
            <div v-if="form.errors.title" class="text-sm text-red-600 mt-1">
                {{ form.errors.title }}
            </div>
        </div>

        <!-- Logged at -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">Logged At</label>
            <DateTimeField v-model="form.logged_at" />
            <div v-if="form.errors.logged_at" class="text-sm text-red-600 mt-1">
                {{ form.errors.logged_at }}
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm text-gray-500 mb-2">Description</label>
            <textarea
                v-model="form.description"
                rows="6"
                class="w-full border rounded-lg px-4 py-2"
                placeholder="Write a clear timeline note..."
            />
            <div
                v-if="form.errors.description"
                class="text-sm text-red-600 mt-1"
            >
                {{ form.errors.description }}
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a
                :href="returnUrl || route('logs.index')"
                class="w-full sm:w-auto px-4 py-2 rounded-lg border text-center"
            >
                Cancel
            </a>

            <button
                @click="submit"
                class="w-full sm:w-auto bg-black text-white px-6 py-2 rounded-lg"
                :disabled="form.processing"
            >
                {{ isEdit ? "Update Log" : "Save Log" }}
            </button>
        </div>
    </div>

    <!-- System Picker Modal -->
    <div
        v-if="systemPickerOpen"
        class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4"
        @click.self="systemPickerOpen = false"
    >
        <div
            class="w-full max-w-xl rounded-2xl bg-white shadow-lg border border-slate-200 overflow-hidden"
        >
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

                <div
                    class="mt-4 max-h-[55vh] overflow-auto divide-y border rounded-xl"
                >
                    <button
                        v-for="s in systemPickerList"
                        :key="s.id"
                        type="button"
                        class="w-full text-left p-4 hover:bg-slate-50 transition"
                        @click="pickSystem(s.id)"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div
                                    class="text-sm font-medium text-slate-900 truncate"
                                >
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
        <div
            class="w-full max-w-xl rounded-2xl bg-white shadow-lg border border-slate-200 overflow-hidden"
        >
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">
                        Pilih Feature
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ selectedSystem ? `System: ${selectedSystem.name}` : "Pilih system dulu." }}
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
                    :disabled="!selectedSystem"
                />

                <div
                    class="mt-4 max-h-[55vh] overflow-auto divide-y border rounded-xl"
                >
                    <button
                        type="button"
                        class="w-full text-left p-4 hover:bg-slate-50 transition"
                        @click="pickFeature('')"
                        :disabled="!selectedSystem"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div
                                    class="text-sm font-medium text-slate-900 truncate"
                                >
                                    Global (tanpa feature)
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Log tidak terikat ke feature.
                                </div>
                            </div>
                            <span
                                v-if="!form.feature_id"
                                class="text-xs font-semibold text-[#AF4324]"
                            >
                                Terpilih
                            </span>
                        </div>
                    </button>

                    <button
                        v-for="f in featurePickerList"
                        :key="f.id"
                        type="button"
                        class="w-full text-left p-4 hover:bg-slate-50 transition disabled:opacity-50"
                        @click="pickFeature(f.id)"
                        :disabled="!selectedSystem"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div
                                    class="text-sm font-medium text-slate-900 truncate"
                                >
                                    {{ f.title }}
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
                        v-if="selectedSystem && (featurePickerList ?? []).length === 0"
                        class="p-6 text-center text-sm text-slate-500"
                    >
                        Tidak ada feature yang cocok.
                    </div>

                    <div
                        v-if="!selectedSystem"
                        class="p-6 text-center text-sm text-slate-500"
                    >
                        Pilih system terlebih dahulu.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reference Modal -->
    <div
        v-if="referenceModalOpen"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
    >
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl">
            <div class="p-5 border-b flex items-center justify-between">
                <div>
                    <div class="font-semibold text-gray-900">
                        Pilih Reference Logs
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        {{
                            form.feature_id
                                ? "Filter: bug open untuk feature terpilih"
                                : form.type === "fix"
                                ? "Filter: bug open"
                                : "Semua log (dibatasi list terbaru)"
                        }}
                    </div>
                </div>

                <button
                    type="button"
                    class="px-3 py-1.5 rounded-lg border text-sm"
                    @click="referenceModalOpen = false"
                >
                    Close
                </button>
            </div>

            <div class="p-5 max-h-[60vh] overflow-auto">
                <div class="mb-4">
                    <input
                        v-model="referenceSearch"
                        type="text"
                        class="w-full border rounded-lg px-4 py-2 text-sm"
                        placeholder="Cari judul..."
                    />
                </div>

                <div
                    v-if="referenceCandidates.length === 0"
                    class="text-sm text-gray-400 text-center py-10"
                >
                    Tidak ada data
                </div>

                <div v-else class="divide-y rounded-lg border">
                    <label
                        v-for="refLog in referenceCandidates"
                        :key="refLog.id"
                        class="flex items-start gap-3 p-4 hover:bg-gray-50 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            class="mt-1"
                            :checked="isSelectedRef(refLog.id)"
                            @change="toggleReference(refLog.id)"
                            :disabled="referenceLocked"
                        />
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900 truncate">
                                {{ refLog.title }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium mr-1"
                                    :class="logTypeMap[refLog.type] || 'bg-gray-100 text-gray-600'"
                                >
                                    {{ logTypeLabel[refLog.type] || refLog.type }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="(refLog.status && (logStatusMap[refLog.status] || 'bg-gray-100 text-gray-600')) || 'bg-gray-50 text-gray-400'"
                                >
                                    {{ (refLog.status && (logStatusLabel[refLog.status] || refLog.status)) || "No Status" }}
                                </span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="p-5 border-t flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Terpilih: {{ (form.reference_ids || []).length }}
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="(form.reference_ids || []).length > 0"
                        type="button"
                        class="px-4 py-2 rounded-lg border text-sm"
                        @click="clearReferences"
                        :disabled="referenceLocked"
                    >
                        Clear
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 rounded-lg bg-black text-white text-sm"
                        @click="referenceModalOpen = false"
                    >
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
