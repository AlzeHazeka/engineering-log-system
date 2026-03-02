<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    status: "active",
    description: "",
    repository_url: "",
});

const submit = () => {
    form.post(route("systems.store"));
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Create System</h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4">
                <div class="bg-white rounded-xl shadow border p-8 space-y-6">
                    <div>
                        <label class="block text-sm mb-1">Name</label>
                        <input
                            v-model="form.name"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full border rounded-lg px-4 py-2"
                        >
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="paused">Paused</option>
                            <option value="deprecated">Deprecated</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Repository URL</label>
                        <input
                            v-model="form.repository_url"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <div class="flex justify-end gap-4">
                        <a
                            :href="route('systems.index')"
                            class="px-4 py-2 border rounded-lg"
                        >
                            Cancel
                        </a>

                        <button
                            @click="submit"
                            class="bg-black text-white px-6 py-2 rounded-lg"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
