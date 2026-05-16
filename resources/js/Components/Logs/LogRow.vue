<script setup>
import Badge from "@/Components/Badge.vue";
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { timeAgo } from "@/Utils/datetime";
import { Eye, Pencil, Trash2, CheckCircle2 } from "lucide-vue-next";

const props = defineProps({
    log: Object,
    logTypeMap: Object,
    logTypeLabel: Object,
    impactMap: Object,
    impactLabel: Object,
    logStatusMap: Object,
    logStatusLabel: Object,
});

const emit = defineEmits(["delete", "mark-done"]);

const page = usePage();
const currentUrl = computed(() => page.url?.value ?? page.url ?? "");

const impactBadge = computed(() => {
    if (!props.log.impact) {
        return null;
    }

    return {
        label: props.impactLabel[props.log.impact] ?? props.log.impact,
        colorClass:
            props.impactMap[props.log.impact] ?? "bg-gray-100 text-gray-500",
    };
});

const statusBadge = computed(() => {
    if (!props.log.status) return null;

    return {
        label: props.logStatusLabel[props.log.status] ?? props.log.status,
        colorClass:
            props.logStatusMap[props.log.status] ?? "bg-gray-100 text-gray-500",
    };
});

const deleteLog = () => {
    emit("delete", props.log.id);
};

const markDone = () => {
    emit("mark-done", props.log);
};

const openLog = () => {
    const url = currentUrl.value
        ? `${route("logs.show", props.log.id)}?return=${encodeURIComponent(
              currentUrl.value
          )}`
        : route("logs.show", props.log.id);
    router.visit(url);
};
</script>

<template>
    <tr class="border-t hover:bg-gray-50 cursor-pointer" @click="openLog">
        <td class="p-4">
            <div class="text-gray-900">
                {{ timeAgo(log.logged_at) }}
            </div>
            <div class="text-xs text-gray-500">
                {{ log.formatted_time }}
            </div>
        </td>

        <td class="p-4">
            {{ log.system.name }}
        </td>

        <td class="p-4">
            <span v-if="log.feature" class="text-gray-900">
                {{ log.feature.title }}
            </span>
            <span v-else class="text-gray-400">—</span>
        </td>

        <td class="p-4 font-medium">
            <div class="text-slate-900 whitespace-normal break-words">
                {{ log.title }}
            </div>
        </td>

        <td class="p-4">
            <Badge
                :label="logTypeLabel[log.type]"
                :colorClass="logTypeMap[log.type]"
            />
        </td>

        <td class="p-4">
            <Badge
                v-if="impactBadge"
                :label="impactBadge.label"
                :colorClass="impactBadge.colorClass"
            />
            <span v-else class="text-gray-400">—</span>
        </td>

        <td class="p-4">
            <Badge
                v-if="statusBadge"
                :label="statusBadge.label"
                :colorClass="statusBadge.colorClass"
            />
            <span v-else class="text-gray-400">—</span>

            <div
                v-if="log.type === 'fix' && log.status === 'resolved' && log.sla_on_time !== null && log.sla_on_time !== undefined"
                class="mt-1 text-xs text-gray-500"
            >
                SLA:
                <span class="font-medium text-gray-700">
                    {{ log.sla_duration_days ?? "—" }}d/{{ log.sla_days ?? "—" }}d
                </span>
                ·
                <span
                    class="font-medium"
                    :class="log.sla_on_time ? 'text-green-700' : 'text-red-700'"
                >
                    {{ log.sla_on_time ? "On Time" : "Late" }}
                </span>
            </div>
        </td>

        <td class="p-4 text-right w-32 whitespace-nowrap" @click.stop>
            <div class="flex items-center justify-end gap-2">
                <button
                    v-if="log.type === 'progress' && log.status === 'on_progress'"
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-[#AF4324] hover:bg-[#AF4324]/10 transition"
                    title="Mark as Done"
                    aria-label="Mark as Done"
                    @click="markDone"
                >
                    <CheckCircle2 class="h-4 w-4" />
                </button>

                <a
                    :href="
                        currentUrl
                            ? `${route('logs.show', log.id)}?return=${encodeURIComponent(currentUrl)}`
                            : route('logs.show', log.id)
                    "
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition"
                    title="View"
                    aria-label="View"
                >
                    <Eye class="h-4 w-4" />
                </a>

                <a
                    :href="
                        currentUrl
                            ? `${route('logs.edit', log.id)}?return=${encodeURIComponent(currentUrl)}`
                            : route('logs.edit', log.id)
                    "
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition"
                    title="Edit"
                    aria-label="Edit"
                >
                    <Pencil class="h-4 w-4" />
                </a>

                <button
                    @click="deleteLog"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-red-50 hover:text-red-700 transition"
                    title="Delete"
                    aria-label="Delete"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>
        </td>
    </tr>
</template>
