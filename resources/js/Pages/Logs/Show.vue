<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { router } from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import {
    logTypeMap,
    logTypeLabel,
    impactMap,
    impactLabel,
} from "@/Utils/enums";

const props = defineProps({
    log: Object,
});

const deleteLog = () => {
    if (confirm("Are you sure you want to delete this log?")) {
        router.delete(route("logs.destroy", props.log.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Log Detail</h2>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4">
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

                    <!-- Type & Impact -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                :label="impactLabel[log.impact]"
                                :colorClass="impactMap[log.impact]"
                            />
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1">
                            Date & Time
                        </label>
                        <div>
                            {{ new Date(log.logged_at).toLocaleString() }}
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-2">
                            Description
                        </label>

                        <div
                            class="prose max-w-none border rounded-lg p-4 bg-gray-50"
                            v-html="log.description"
                        />
                    </div>

                    <!-- Metadata -->
                    <div class="text-xs text-gray-400 pt-4 border-t">
                        Created: {{ new Date(log.created_at).toLocaleString() }}
                        <br />
                        Updated: {{ new Date(log.updated_at).toLocaleString() }}
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between pt-6">
                        <a
                            :href="route('logs.index')"
                            class="px-4 py-2 rounded-lg border"
                        >
                            Back
                        </a>

                        <div class="flex gap-4">
                            <a
                                :href="route('logs.edit', log.id)"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white"
                            >
                                Edit
                            </a>

                            <button
                                @click="deleteLog"
                                class="px-4 py-2 rounded-lg bg-red-600 text-white"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
