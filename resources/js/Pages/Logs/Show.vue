<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { ArrowLeft, Pencil, Trash2, CheckCircle2 } from "lucide-vue-next";
import Badge from "@/Components/Badge.vue";
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
import { timeAgo } from "@/Utils/datetime";

const props = defineProps({
    log: Object,
    returnUrl: { type: String, default: null },
});

const showMarkDone = computed(
    () => props.log?.type === "progress" && props.log?.status === "on_progress"
);

const markDoneOpen = ref(false);

const openMarkDone = () => {
    markDoneOpen.value = true;
};

const confirmMarkDone = () => {
    const url = props.returnUrl
        ? `${route("logs.markDone", props.log.id)}?return=${encodeURIComponent(
              props.returnUrl
          )}`
        : route("logs.markDone", props.log.id);

    router.put(
        url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                markDoneOpen.value = false;
            },
        }
    );
};

const impactBadge = computed(() => {
    if (!props.log.impact) {
        return null;
    }

    return {
        label: impactLabel[props.log.impact] ?? props.log.impact,
        colorClass: impactMap[props.log.impact] ?? "bg-gray-100 text-gray-500",
    };
});

const statusBadge = computed(() => {
    if (!props.log.status) return null;

    return {
        label: logStatusLabel[props.log.status] ?? props.log.status,
        colorClass:
            logStatusMap[props.log.status] ?? "bg-gray-100 text-gray-500",
    };
});

const slaBadge = computed(() => {
    if (props.log.type !== "fix" || props.log.status !== "resolved") return null;
    if (props.log.sla_on_time === null || props.log.sla_on_time === undefined)
        return null;

    return props.log.sla_on_time
        ? { label: "On Time", colorClass: "bg-green-100 text-green-700" }
        : { label: "Late", colorClass: "bg-red-100 text-red-700" };
});

const deleteLog = () => {
    deleteConfirmOpen.value = true;
};

const deleteConfirmOpen = ref(false);

const confirmDelete = () => {
    const url = props.returnUrl
        ? `${route("logs.destroy", props.log.id)}?return=${encodeURIComponent(
              props.returnUrl
          )}`
        : route("logs.destroy", props.log.id);

    router.delete(url, {
        preserveScroll: true,
        onFinish: () => {
            deleteConfirmOpen.value = false;
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 sm:py-8">
            <div class="max-w-4xl mx-auto px-4 space-y-4">
                <div
                    class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
                >
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Log Detail
                        </h2>
                        <p class="text-sm text-gray-500">
                            Timeline entry information.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            :href="returnUrl || route('logs.index')"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                            title="Back"
                            aria-label="Back"
                        >
                            <ArrowLeft class="h-5 w-5" />
                        </a>

                        <button
                            v-if="showMarkDone"
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-[#AF4324] hover:bg-[#AF4324]/10 transition"
                            title="Mark as Done"
                            aria-label="Mark as Done"
                            @click="openMarkDone"
                        >
                            <CheckCircle2 class="h-5 w-5" />
                        </button>

                        <a
                            :href="
                                returnUrl
                                    ? `${route('logs.edit', log.id)}?return=${encodeURIComponent(returnUrl)}`
                                    : route('logs.edit', log.id)
                            "
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                            title="Edit"
                            aria-label="Edit"
                        >
                            <Pencil class="h-5 w-5" />
                        </a>

                        <button
                            type="button"
                            @click="deleteLog"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-red-700 hover:bg-red-50 transition"
                            title="Delete"
                            aria-label="Delete"
                        >
                            <Trash2 class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow border p-5 sm:p-8 space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >Title</label
                        >
                        <div class="text-lg font-semibold">
                            {{ log.title }}
                        </div>
                    </div>

                    <!-- System -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >System</label
                        >
                        <div>
                            {{ log.system.name }}
                        </div>
                    </div>

                    <!-- Feature -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >Feature</label
                        >
                        <div>
                            <span v-if="log.feature" class="text-gray-900">
                                {{ log.feature.title }}
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </div>
                    </div>

                    <!-- Type / Impact / Status -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm text-gray-500 mb-1"
                                >Type</label
                            >
                            <Badge
                                :label="logTypeLabel[log.type]"
                                :colorClass="logTypeMap[log.type]"
                            />
                        </div>

                        <div>
                            <label class="block text-sm text-gray-500 mb-1"
                                >Impact</label
                            >
                            <Badge
                                v-if="impactBadge"
                                :label="impactBadge.label"
                                :colorClass="impactBadge.colorClass"
                            />
                            <span v-else class="text-gray-400">—</span>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-500 mb-1"
                                >Status</label
                            >
                            <Badge
                                v-if="statusBadge"
                                :label="statusBadge.label"
                                :colorClass="statusBadge.colorClass"
                            />
                            <span v-else class="text-gray-400">—</span>
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1">
                            Date & Time
                        </label>
                        <div>
                            {{ log.formatted_datetime }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ timeAgo(log.logged_at) }}
                        </div>
                    </div>

                    <!-- SLA -->
                    <div v-if="slaBadge" class="rounded-lg border p-4">
                        <div class="text-sm text-gray-500 mb-2">
                            SLA (Bug Resolution)
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <Badge
                                :label="slaBadge.label"
                                :colorClass="slaBadge.colorClass"
                            />
                            <div class="text-sm text-gray-600">
                                {{ log.sla_duration_days ?? "—" }}d /
                                {{ log.sla_days ?? "—" }}d
                            </div>
                        </div>
                    </div>

                    <!-- References -->
                    <div
                        v-if="(log.references?.length ?? 0) > 0"
                        class="rounded-lg border p-4"
                    >
                        <div class="text-sm text-gray-500 mb-2">
                            Terkait dengan log:
                        </div>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            <li v-for="r in log.references" :key="r.id">
                                <a
                                    :href="
                                        returnUrl
                                            ? `${route('logs.show', r.id)}?return=${encodeURIComponent(returnUrl)}`
                                            : route('logs.show', r.id)
                                    "
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ r.title }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-2">
                            Description
                        </label>

                        <div
                            class="border rounded-lg p-4 bg-gray-50 text-sm text-gray-800 whitespace-pre-wrap"
                        >
                            {{ log.description }}
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="text-xs text-gray-400 pt-4 border-t">
                        Created: {{ log.formatted_created_at }}
                        <br />
                        Updated: {{ log.formatted_updated_at }}
                    </div>

                    <!-- Actions -->
                    <div class="pt-6" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <Modal :show="markDoneOpen" maxWidth="lg" @close="markDoneOpen = false">
        <div class="p-6">
            <div class="text-lg font-semibold text-slate-900">
                Tandai log sebagai Done?
            </div>
            <div class="mt-2 text-sm text-slate-600">
                Ini akan mengubah status progress menjadi
                <span class="font-medium">Done</span>.
            </div>

            <div
                class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
            >
                <div class="text-sm font-medium text-slate-900">
                    {{ log.title }}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    {{ log.system?.name ?? "—" }}
                    <span v-if="log.feature"> · {{ log.feature.title }}</span>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                    @click="markDoneOpen = false"
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

    <ConfirmModal
        :show="deleteConfirmOpen"
        title="Hapus log?"
        description="Tindakan ini tidak bisa dibatalkan."
        confirmText="Ya, hapus"
        cancelText="Batal"
        tone="danger"
        @close="deleteConfirmOpen = false"
        @confirm="confirmDelete"
    >
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-sm font-medium text-slate-900">
                {{ log.title }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ log.system?.name ?? "—" }}
                <span v-if="log.feature"> · {{ log.feature.title }}</span>
            </div>
        </div>
    </ConfirmModal>
</template>
