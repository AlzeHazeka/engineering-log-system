<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Badge from "@/Components/Badge.vue";
import ProgressBar from "@/Components/ProgressBar.vue";
import { router } from "@inertiajs/vue3";
import { featureStatusLabel, featureStatusMap } from "@/Utils/enums";
import { ArrowLeft, Plus, Pencil, Trash2 } from "lucide-vue-next";
import { useSoftRevalidate } from "@/Utils/softRevalidate";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { ref } from "vue";

const props = defineProps({
    system: Object,
    features: Array,
});

useSoftRevalidate({ only: ["system", "features"] });

const deleteFeature = (feature) => {
    deleteTarget.value = feature;
    deleteConfirmOpen.value = true;
};

const deleteConfirmOpen = ref(false);
const deleteTarget = ref(null);

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    router.delete(
        route("systems.features.destroy", [props.system.slug, deleteTarget.value.id]),
        {
            preserveScroll: true,
            onFinish: () => {
                deleteConfirmOpen.value = false;
                deleteTarget.value = null;
            },
        }
    );
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 sm:py-8">
            <div class="max-w-6xl mx-auto px-4 space-y-4">
                <div
                    class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
                >
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Features
                        </h2>
                        <p class="text-sm text-gray-500">
                            System: {{ system.name }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            :href="route('systems.show', system.slug)"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                            title="Back"
                            aria-label="Back"
                        >
                            <ArrowLeft class="h-5 w-5" />
                        </a>
                        <a
                            :href="route('systems.features.create', system.slug)"
                            class="inline-flex items-center justify-center rounded-lg bg-black text-white p-2 hover:opacity-90 transition"
                            title="Add feature"
                            aria-label="Add feature"
                        >
                            <Plus class="h-5 w-5" />
                        </a>
                    </div>
                </div>

                <div
                    class="bg-white rounded-xl shadow-sm border overflow-hidden"
                >
                    <div
                        v-if="features.length === 0"
                        class="p-6 text-gray-400 text-center"
                    >
                        No features yet.
                    </div>

                    <div v-else class="divide-y">
                        <div
                            v-for="feature in features"
                            :key="feature.id"
                            class="p-5 hover:bg-gray-50 transition"
                        >
                            <div
                                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="flex items-center gap-2 flex-wrap"
                                    >
                                        <div
                                            class="font-semibold text-gray-900 truncate"
                                        >
                                            {{ feature.title }}
                                        </div>
                                        <Badge
                                            :label="
                                                featureStatusLabel[
                                                    feature.status
                                                ] ?? feature.status
                                            "
                                            :colorClass="
                                                featureStatusMap[
                                                    feature.status
                                                ] ??
                                                'bg-gray-100 text-gray-700'
                                            "
                                        />
                                    </div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        Team:
                                        {{ feature.assigned_team || "—" }}
                                    </div>
                                </div>

                                <div class="w-full md:w-72">
                                    <ProgressBar
                                        :value="feature.progress ?? 0"
                                    />
                                </div>

                                <div class="flex gap-3 md:justify-end">
                                    <a
                                        :href="
                                            route('systems.features.edit', [
                                                system.slug,
                                                feature.id,
                                            ])
                                        "
                                        class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition"
                                        title="Edit"
                                        aria-label="Edit"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </a>
                                    <button
                                        @click="deleteFeature(feature)"
                                        class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-red-50 hover:text-red-700 transition"
                                        title="Delete"
                                        aria-label="Delete"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <ConfirmModal
        :show="deleteConfirmOpen"
        title="Hapus feature?"
        description="Tindakan ini tidak bisa dibatalkan."
        confirmText="Ya, hapus"
        cancelText="Batal"
        tone="danger"
        @close="deleteConfirmOpen = false"
        @confirm="confirmDelete"
    >
        <div v-if="deleteTarget" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-sm font-medium text-slate-900">
                {{ deleteTarget.title }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                System: {{ system.name }}
            </div>
        </div>
    </ConfirmModal>
</template>
