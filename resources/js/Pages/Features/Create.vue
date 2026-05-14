<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FeatureForm from "./Form.vue";
import { ArrowLeft } from "lucide-vue-next";

const props = defineProps({
    system: Object,
    feature: Object,
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Add Feature
                        </h2>
                        <p class="text-sm text-gray-500">
                            Create a feature for {{ system.name }}
                        </p>
                    </div>

                    <a
                        :href="route('systems.show', system.slug)"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                        title="Back"
                        aria-label="Back"
                    >
                        <ArrowLeft class="h-5 w-5" />
                    </a>
                </div>

                <FeatureForm
                    :system="system"
                    :feature="null"
                    method="post"
                    :submitUrl="route('systems.features.store', system.slug)"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
