<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { ArrowLeft, Pencil, Trash2 } from "lucide-vue-next";
import Badge from "@/Components/Badge.vue";
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
});

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
    if (confirm("Are you sure you want to delete this log?")) {
        router.delete(route("logs.destroy", props.log.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Log Detail
                        </h2>
                        <p class="text-sm text-gray-500">
                            Timeline entry information.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            :href="route('logs.index')"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                            title="Back"
                            aria-label="Back"
                        >
                            <ArrowLeft class="h-5 w-5" />
                        </a>

                        <a
                            :href="route('logs.edit', log.id)"
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

                <div class="bg-white rounded-xl shadow border p-8 space-y-6">
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
                                    :href="route('logs.show', r.id)"
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
</template>
