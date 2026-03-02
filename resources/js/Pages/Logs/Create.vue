<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm } from "@inertiajs/vue3";
import Editor from "@/Components/Editor.vue";
import { logTypeLabel, impactLabel } from "@/Utils/enums";

const props = defineProps({
    systems: Array,
    types: Array,
    impacts: Array,
});

const form = useForm({
    system_id: "",
    type: "",
    impact: "low",
    title: "",
    description: "",
    logged_at: new Date().toISOString().slice(0, 16),
});

const submit = () => {
    form.post(route("logs.store"));
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Create Log</h2>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4">
                <div class="bg-white rounded-xl shadow border p-8 space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >Title</label
                        >
                        <input
                            v-model="form.title"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <!-- System -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >System</label
                        >
                        <select
                            v-model="form.system_id"
                            class="w-full border rounded-lg px-4 py-2"
                        >
                            <option value="">Select System</option>
                            <option
                                v-for="system in systems"
                                :key="system.id"
                                :value="system.id"
                            >
                                {{ system.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Type & Impact Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm text-gray-500 mb-1"
                                >Type</label
                            >
                            <select
                                v-model="form.type"
                                class="w-full border rounded-lg px-4 py-2"
                            >
                                <option value="">Select Type</option>
                                <option
                                    v-for="type in types"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ logTypeLabel[type] }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-500 mb-1"
                                >Impact</label
                            >
                            <select
                                v-model="form.impact"
                                class="w-full border rounded-lg px-4 py-2"
                            >
                                <option
                                    v-for="impact in impacts"
                                    :key="impact"
                                    :value="impact"
                                >
                                    {{ impactLabel[impact] }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >Date & Time</label
                        >
                        <input
                            type="datetime-local"
                            v-model="form.logged_at"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-2"
                            >Description</label
                        >
                        <Editor v-model="form.description" />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-4 pt-4">
                        <a
                            :href="route('logs.index')"
                            class="px-4 py-2 rounded-lg border"
                        >
                            Cancel
                        </a>

                        <button
                            @click="submit"
                            class="bg-black text-white px-6 py-2 rounded-lg"
                            :disabled="form.processing"
                        >
                            Save Log
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
