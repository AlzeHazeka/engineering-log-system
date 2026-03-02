<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    filters: Object,
    systems: Array,
    logTypeLabel: Object,
    impactLabel: Object,
});

const emit = defineEmits(["update", "reset"]);

const date = ref(props.filters.date);
const systemId = ref(props.filters.system_id);
const type = ref(props.filters.type);
const impact = ref(props.filters.impact);

watch([date, systemId, type, impact], () => {
    emit("update", {
        date: date.value,
        system_id: systemId.value,
        type: type.value,
        impact: impact.value,
    });
});

const reset = () => {
    emit("reset");
};
</script>

<template>
    <div class="bg-white rounded-xl shadow border p-6 space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
        >
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Daily Logs</h2>
                <p class="text-sm text-gray-500">
                    Showing logs for selected date.
                </p>
            </div>

            <a
                :href="route('logs.create')"
                class="inline-flex items-center bg-black text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition"
            >
                + Create Log
            </a>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-4">
            <!-- Date -->
            <div>
                <label class="block text-sm text-gray-500 mb-1">Date</label>
                <input
                    type="date"
                    v-model="date"
                    class="border rounded-lg px-3 py-2"
                />
            </div>

            <!-- System -->
            <div>
                <label class="block text-sm text-gray-500 mb-1">System</label>
                <select v-model="systemId" class="border rounded-lg px-3 py-2">
                    <option value="">All</option>
                    <option
                        v-for="system in systems"
                        :key="system.id"
                        :value="system.id"
                    >
                        {{ system.name }}
                    </option>
                </select>
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm text-gray-500 mb-1">Type</label>
                <select v-model="type" class="border rounded-lg px-3 py-2">
                    <option value="">All</option>
                    <option
                        v-for="(label, key) in logTypeLabel"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </select>
            </div>

            <!-- Impact -->
            <div>
                <label class="block text-sm text-gray-500 mb-1">Impact</label>
                <select v-model="impact" class="border rounded-lg px-3 py-2">
                    <option value="">All</option>
                    <option
                        v-for="(label, key) in impactLabel"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </select>
            </div>
        </div>

        <button @click="reset" class="text-sm text-gray-500 hover:underline">
            Reset Filter
        </button>
    </div>
</template>
