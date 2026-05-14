<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    bug_resolution_days: Object,
});

const form = useForm({
    bug_resolution_days: {
        low: props.bug_resolution_days?.low ?? 5,
        medium: props.bug_resolution_days?.medium ?? 3,
        high: props.bug_resolution_days?.high ?? 2,
        critical: props.bug_resolution_days?.critical ?? 1,
    },
});

const submit = () => {
    form.put(route("settings.sla.update"), { preserveScroll: true });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="space-y-6 max-w-3xl">
            <div>
                <div class="text-xl font-semibold text-slate-900">
                    Settings · Variables · SLA
                </div>
                <div class="text-sm text-slate-500">
                    Atur durasi SLA (hari) untuk resolusi bug berdasarkan impact.
                </div>
            </div>

            <form
                class="rounded-xl border border-slate-200 bg-white p-6 space-y-5"
                @submit.prevent="submit"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1"
                            >Low (days)</label
                        >
                        <input
                            v-model.number="form.bug_resolution_days.low"
                            type="number"
                            min="0"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                        <div
                            v-if="form.errors['bug_resolution_days.low']"
                            class="text-sm text-red-600 mt-1"
                        >
                            {{ form.errors["bug_resolution_days.low"] }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1"
                            >Medium (days)</label
                        >
                        <input
                            v-model.number="form.bug_resolution_days.medium"
                            type="number"
                            min="0"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                        <div
                            v-if="form.errors['bug_resolution_days.medium']"
                            class="text-sm text-red-600 mt-1"
                        >
                            {{ form.errors["bug_resolution_days.medium"] }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1"
                            >High (days)</label
                        >
                        <input
                            v-model.number="form.bug_resolution_days.high"
                            type="number"
                            min="0"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                        <div
                            v-if="form.errors['bug_resolution_days.high']"
                            class="text-sm text-red-600 mt-1"
                        >
                            {{ form.errors["bug_resolution_days.high"] }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1"
                            >Critical (days)</label
                        >
                        <input
                            v-model.number="form.bug_resolution_days.critical"
                            type="number"
                            min="0"
                            class="w-full border rounded-lg px-4 py-2"
                        />
                        <div
                            v-if="form.errors['bug_resolution_days.critical']"
                            class="text-sm text-red-600 mt-1"
                        >
                            {{ form.errors["bug_resolution_days.critical"] }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-white hover:bg-slate-800 transition disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        Save
                    </button>
                    <div v-if="form.recentlySuccessful" class="text-sm text-green-700">
                        Saved.
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

