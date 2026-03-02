<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    systems: Array,
});

const form = useForm({
    scope: "all",
    date: "",
    month: "",
    start_date: "",
    end_date: "",
    system_id: "",
    type: "",
    impact: "",
    format: "pdf",
});

const submit = () => {
    window.location = route("reports.export", form.data());
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Report Center</h2>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4">
                <div class="bg-white rounded-xl shadow border p-8 space-y-6">
                    <!-- Scope -->
                    <div>
                        <label class="block text-sm mb-2">Scope</label>
                        <select
                            v-model="form.scope"
                            class="w-full border rounded-lg px-4 py-2"
                        >
                            <option value="all">All</option>
                            <option value="daily">Daily</option>
                            <option value="monthly">Monthly</option>
                            <option value="range">Date Range</option>
                        </select>
                    </div>

                    <!-- Date Inputs -->
                    <div v-if="form.scope === 'daily'">
                        <input
                            type="date"
                            v-model="form.date"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <div
                        v-if="form.scope === 'range'"
                        class="grid grid-cols-2 gap-4"
                    >
                        <input
                            type="date"
                            v-model="form.start_date"
                            class="border rounded-lg px-4 py-2"
                        />

                        <input
                            type="date"
                            v-model="form.end_date"
                            class="border rounded-lg px-4 py-2"
                        />
                    </div>

                    <div v-if="form.scope === 'monthly'">
                        <input
                            type="month"
                            v-model="form.month"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                    </div>

                    <!-- System -->
                    <div>
                        <label class="block text-sm mb-2">System</label>
                        <select
                            v-model="form.system_id"
                            class="w-full border rounded-lg px-4 py-2"
                        >
                            <option value="">All Systems</option>
                            <option
                                v-for="system in systems"
                                :key="system.id"
                                :value="system.id"
                            >
                                {{ system.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Format -->
                    <div>
                        <label class="block text-sm mb-2">Format</label>
                        <select
                            v-model="form.format"
                            class="w-full border rounded-lg px-4 py-2"
                        >
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button
                            @click="submit"
                            class="bg-black text-white px-6 py-2 rounded-lg"
                        >
                            Generate Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
