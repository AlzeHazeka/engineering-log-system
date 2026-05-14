<script setup>
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import ProgressBar from "@/Components/ProgressBar.vue";
import Badge from "@/Components/Badge.vue";
import DateField from "@/Components/DateField.vue";
import {
    featureStatusLabel,
    featureCategoryLabel,
} from "@/Utils/enums";
import { formatDate } from "@/Utils/datetime";

const props = defineProps({
    system: Object,
    feature: { type: Object, default: null },
    submitUrl: String,
    method: { type: String, default: "post" }, // post | put
});

const isEdit = computed(() => !!props.feature);

const completionMeta = computed(() => {
    if (!props.feature?.completed_at) return null;

    const completedAt = new Date(props.feature.completed_at);
    const dueDate = props.feature?.due_date ? new Date(props.feature.due_date) : null;

    if (Number.isNaN(completedAt.getTime())) return null;

    let onTime = null;
    if (dueDate && !Number.isNaN(dueDate.getTime())) {
        const dueEnd = new Date(dueDate);
        dueEnd.setHours(23, 59, 59, 999);
        onTime = completedAt.getTime() <= dueEnd.getTime();
    }

    return {
        completedAtLabel: formatDate(props.feature.completed_at),
        onTime,
    };
});

const form = useForm({
    title: props.feature?.title || "",
    description: props.feature?.description || "",
    status: props.feature?.status || "planned",
    progress: props.feature?.progress ?? 0,
    category: props.feature?.category || "feature",
    start_date: props.feature?.start_date || "",
    due_date: props.feature?.due_date || "",
    assigned_team: props.feature?.assigned_team || "",
});

watch(
    () => form.status,
    (status) => {
        if (status === "done") {
            form.progress = 100;
        }
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
    <div class="bg-white rounded-xl shadow-sm border p-6 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ isEdit ? "Edit Feature" : "Create Feature" }}
                </h2>
                <p class="text-sm text-gray-500">
                    System: <span class="font-medium">{{ system.name }}</span>
                </p>
            </div>
        </div>

        <!-- Title -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">Title</label>
            <input
                v-model="form.title"
                class="w-full border rounded-lg px-4 py-2"
                placeholder="e.g. Product Management"
            />
            <div v-if="form.errors.title" class="text-sm text-red-600 mt-1">
                {{ form.errors.title }}
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">Description</label>
            <textarea
                v-model="form.description"
                rows="4"
                class="w-full border rounded-lg px-4 py-2"
                placeholder="Optional short context…"
            />
            <div
                v-if="form.errors.description"
                class="text-sm text-red-600 mt-1"
            >
                {{ form.errors.description }}
            </div>
        </div>

        <!-- Status / Category -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Status</label>
                <select
                    v-model="form.status"
                    class="w-full border rounded-lg px-4 py-2"
                >
                    <option value="planned">{{ featureStatusLabel.planned }}</option>
                    <option value="in_progress">
                        {{ featureStatusLabel.in_progress }}
                    </option>
                    <option value="on_hold">{{ featureStatusLabel.on_hold }}</option>
                    <option value="done">{{ featureStatusLabel.done }}</option>
                </select>
                <div
                    v-if="form.errors.status"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.status }}
                </div>

                <div
                    v-if="isEdit && feature?.status === 'done' && completionMeta"
                    class="mt-3 text-sm"
                >
                    <div class="text-gray-500">
                        Completed: <span class="text-gray-900 font-medium">{{ completionMeta.completedAtLabel }}</span>
                    </div>

                    <div v-if="completionMeta.onTime !== null" class="mt-2">
                        <Badge
                            v-if="completionMeta.onTime"
                            label="On Time"
                            colorClass="bg-green-100 text-green-700"
                        />
                        <Badge
                            v-else
                            label="Late"
                            colorClass="bg-red-100 text-red-700"
                        />
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">Category</label>
                <select
                    v-model="form.category"
                    class="w-full border rounded-lg px-4 py-2"
                >
                    <option value="feature">
                        {{ featureCategoryLabel.feature }}
                    </option>
                    <option value="improvement">
                        {{ featureCategoryLabel.improvement }}
                    </option>
                    <option value="maintenance">
                        {{ featureCategoryLabel.maintenance }}
                    </option>
                </select>
                <div
                    v-if="form.errors.category"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.category }}
                </div>
            </div>
        </div>

        <!-- Progress -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="block text-sm text-gray-500">Progress</label>
                <div class="text-sm font-medium text-gray-900">
                    {{ Number(form.progress || 0) }}%
                </div>
            </div>
            <input
                v-model.number="form.progress"
                type="range"
                min="0"
                max="100"
                class="w-full"
                :disabled="form.status === 'done'"
            />
            <ProgressBar :value="Number(form.progress || 0)" />
            <div
                v-if="form.errors.progress"
                class="text-sm text-red-600 mt-1"
            >
                {{ form.errors.progress }}
            </div>
        </div>

        <!-- Dates / Team -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm text-gray-500 mb-1"
                    >Start Date</label
                >
                <DateField v-model="form.start_date" />
                <div
                    v-if="form.errors.start_date"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.start_date }}
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">Due Date</label>
                <DateField v-model="form.due_date" />
                <div
                    v-if="form.errors.due_date"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.due_date }}
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1"
                    >Assigned Team</label
                >
                <input
                    v-model="form.assigned_team"
                    class="w-full border rounded-lg px-4 py-2"
                    placeholder="e.g. POS Squad"
                />
                <div
                    v-if="form.errors.assigned_team"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.assigned_team }}
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2">
            <a
                :href="route('systems.show', system.slug)"
                class="px-4 py-2 rounded-lg border text-sm"
            >
                Cancel
            </a>

            <button
                @click="submit"
                class="bg-black text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition"
                :disabled="form.processing"
            >
                {{ isEdit ? "Update Feature" : "Save Feature" }}
            </button>
        </div>
    </div>
</template>
