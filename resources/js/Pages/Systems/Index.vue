<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { router } from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import { systemStatusMap, systemStatusLabel } from "@/Utils/enums";
import { ref } from "vue";

const props = defineProps({
    systems: Array,
});

const deleteSystem = (system) => {
    const message =
        system.logs_count > 0
            ? `This system has ${system.logs_count} logs.\nDeleting will remove all related logs.\n\nContinue?`
            : "Are you sure you want to delete this system?";

    if (confirm(message)) {
        router.delete(route("systems.destroy", system.slug));
    }
};

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
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Systems</h2>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 space-y-6">
                <!-- Header Action -->
                <div class="flex justify-end">
                    <a
                        :href="route('systems.create')"
                        class="bg-black text-white px-4 py-2 rounded-lg"
                    >
                        + Create System
                    </a>
                </div>

                <!-- Table Block -->
                <div class="bg-white rounded-xl shadow border overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="p-4">Name</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Logs</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="system in systems"
                                :key="system.id"
                                class="border-t hover:bg-gray-50"
                            >
                                <td class="p-4 font-medium">
                                    {{ system.name }}
                                </td>

                                <td class="p-4">
                                    <Badge
                                        :label="
                                            systemStatusLabel[system.status]
                                        "
                                        :colorClass="
                                            systemStatusMap[system.status]
                                        "
                                    />
                                </td>

                                <td class="p-4">
                                    {{ system.logs_count }}
                                </td>

                                <td class="p-4 text-right space-x-3">
                                    <a
                                        :href="
                                            route('systems.show', system.slug)
                                        "
                                        class="text-gray-600 hover:underline"
                                    >
                                        Show
                                    </a>

                                    <a
                                        :href="
                                            route('systems.edit', system.slug)
                                        "
                                        class="text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </a>

                                    <button
                                        @click="openDeleteModal(system)"
                                        class="text-red-600 hover:underline"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="systems.length === 0">
                                <td
                                    colspan="4"
                                    class="p-6 text-center text-gray-400"
                                >
                                    No systems found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
