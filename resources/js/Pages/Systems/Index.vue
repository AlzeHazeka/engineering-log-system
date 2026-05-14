<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { router } from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import ProgressBar from "@/Components/ProgressBar.vue";
import { Plus, Pencil, Trash2 } from "lucide-vue-next";
import {
    systemStatusMap,
    systemStatusLabel,
    systemStageMap,
    systemStageLabel,
} from "@/Utils/enums";
import { ref } from "vue";

const props = defineProps({
    systems: Array,
});

//Delete Actions
const showDeleteModal = ref(false);
const selectedSystem = ref(null);

const openDeleteModal = (system) => {
    selectedSystem.value = system;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedSystem.value = null;
};

const confirmDelete = () => {
    router.delete(route("systems.destroy", selectedSystem.value.slug));
};

const openSystem = (system) => {
    router.visit(route("systems.show", system.slug));
};

const percent = (value) => {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) return 0;
    return Math.max(0, Math.min(100, Math.round(n)));
};

const stageColor = (stage) =>
    systemStageMap[stage] ?? "bg-gray-100 text-gray-700";
const stageText = (stage) => systemStageLabel[stage] ?? "Unknown";
</script>

<template>
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Systems
                    </h2>
                    <p class="text-sm text-gray-500">
                        System management workstation overview.
                    </p>
                </div>

                <a
                    :href="route('systems.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-black text-white p-2 hover:opacity-90 transition"
                    title="Create system"
                    aria-label="Create system"
                >
                    <Plus class="h-5 w-5" />
                </a>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Total: {{ systems.length }} systems
                </div>
                <div />
            </div>

            <!-- Table Block -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="p-4">Name</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Stage</th>
                            <th class="p-4">Features</th>
                            <th class="p-4">Logs</th>
                            <th class="p-4">Feature Completion</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="system in systems"
                            :key="system.id"
                            class="border-t hover:bg-gray-50 cursor-pointer"
                            @click="openSystem(system)"
                        >
                            <td class="p-4 font-medium">
                                <div class="text-gray-900">
                                    {{ system.name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ system.slug }}
                                </div>
                            </td>

                            <td class="p-4">
                                <Badge
                                    :label="systemStatusLabel[system.status]"
                                    :colorClass="systemStatusMap[system.status]"
                                />
                            </td>

                            <td class="p-4">
                                <Badge
                                    :label="stageText(system.stage)"
                                    :colorClass="stageColor(system.stage)"
                                />
                            </td>

                            <td class="p-4">
                                {{ system.features_count ?? 0 }}
                            </td>

                            <td class="p-4">
                                {{ system.logs_count }}
                            </td>

                            <td class="p-4">
                                <div class="w-56">
                                    <ProgressBar
                                        :value="percent(system.features_avg_progress)"
                                    />
                                </div>
                            </td>

                            <td class="p-4 text-right space-x-3" @click.stop>
                                <a
                                    :href="route('systems.edit', system.slug)"
                                    class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition"
                                    title="Edit"
                                    aria-label="Edit"
                                >
                                    <Pencil class="h-4 w-4" />
                                </a>

                                <button
                                    @click="openDeleteModal(system)"
                                    class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-red-50 hover:text-red-700 transition"
                                    title="Delete"
                                    aria-label="Delete"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>

                        <tr v-if="systems.length === 0">
                            <td colspan="7" class="p-6 text-center text-gray-400">
                                No systems found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Delete Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        >
            <div
                class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md space-y-4"
            >
                <h3 class="text-lg font-semibold text-red-600">
                    Delete System
                </h3>

                <p class="text-sm text-gray-600">
                    You are about to delete
                    <strong>{{ selectedSystem.name }}</strong
                    >.
                </p>

                <p
                    v-if="selectedSystem.logs_count > 0"
                    class="text-sm text-red-600"
                >
                    This system has {{ selectedSystem.logs_count }} logs. All
                    related logs will also be permanently deleted.
                </p>

                <div class="flex justify-end gap-4 pt-4">
                    <button
                        @click="closeDeleteModal"
                        class="px-4 py-2 border rounded-lg"
                    >
                        Cancel
                    </button>

                    <button
                        @click="confirmDelete"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
