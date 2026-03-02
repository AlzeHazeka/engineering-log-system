<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { systemStatusLabel } from "@/Utils/enums";

const props = defineProps({
    system: Object,
});

const form = useForm({
    name: props.system.name,
    status: props.system.status,
    description: props.system.description || "",
    repository_url: props.system.repository_url || "",
});

const submit = () => {
    form.put(route("systems.update", props.system.slug));
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Edit System</h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4">
                <div class="bg-white rounded-xl shadow border p-8 space-y-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >Name</label
                        >
                        <input
                            v-model="form.name"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1"
                            >Status</label
                        >
                        <select
                            v-model="form.status"
                            class="w-full border rounded-lg px-4 py-2"
                        >
                            <option value="active">
                                {{ systemStatusLabel.active }}
                            </option>
                            <option value="maintenance">
                                {{ systemStatusLabel.maintenance }}
                            </option>
                            <option value="paused">
                                {{ systemStatusLabel.paused }}
                            </option>
                            <option value="deprecated">
                                {{ systemStatusLabel.deprecated }}
                            </option>
                        </select>
                    </div>

                    <!-- Repository -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1">
                            Repository URL
                        </label>
                        <input
                            v-model="form.repository_url"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm text-gray-500 mb-1">
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between pt-6">
                        <a
                            :href="route('systems.show', system.slug)"
                            class="px-4 py-2 border rounded-lg"
                        >
                            Cancel
                        </a>

                        <button
                            @click="submit"
                            class="bg-black text-white px-6 py-2 rounded-lg"
                            :disabled="form.processing"
                        >
                            Update System
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
