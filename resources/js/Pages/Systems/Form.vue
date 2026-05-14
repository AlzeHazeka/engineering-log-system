<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import { systemStatusLabel, systemStageLabel, systemKindLabel } from "@/Utils/enums";
import DateField from "@/Components/DateField.vue";

const props = defineProps({
    system: { type: Object, default: null },
    features: { type: Array, default: () => [] },
    submitUrl: String,
    method: { type: String, default: "post" }, // post | put
});

const isEdit = computed(() => !!props.system);

const form = useForm({
    name: props.system?.name || "",
    kind: props.system?.kind || "app",
    status: props.system?.status || "active",
    stage: props.system?.stage || "",
    released_at: props.system?.released_at || "",
    description: props.system?.description || "",
    repository_url: props.system?.repository_url || "",
    log: null,
});

const original = reactive({
    status: form.status,
    stage: form.stage,
    released_at: form.released_at,
});

const hasLifecycleChange = computed(() => {
    if (!isEdit.value) return false;

    return (
        form.status !== original.status ||
        form.stage !== original.stage ||
        form.released_at !== original.released_at
    );
});

const detectType = () => {
    if (form.status === "maintenance") return "maintenance";
    if (form.stage === "production") return "deployment";
    if (form.status === "paused" || form.status === "deprecated")
        return "decision";
    return "progress";
};

const logDraft = reactive({
    type: detectType(),
    title: "",
    description: "",
    feature_id: "",
});

const logError = ref("");

watch(
    () => [form.status, form.stage, form.released_at],
    () => {
        if (!hasLifecycleChange.value) return;
        logDraft.type = detectType();
    }
);

watch(
    () => hasLifecycleChange.value,
    (changed) => {
        logError.value = "";
        if (!changed) {
            logDraft.type = detectType();
            logDraft.title = "";
            logDraft.description = "";
            logDraft.feature_id = "";
            return;
        }

        // Offer a sensible draft title once lifecycle changes are detected.
        if (!logDraft.title) {
            logDraft.title = "Lifecycle update";
        }
        logDraft.type = detectType();
    }
);

const wantsLog = computed(() => {
    return (
        (logDraft.title || "").trim().length > 0 ||
        (logDraft.description || "").trim().length > 0
    );
});

const submit = () => {
    logError.value = "";

    if (hasLifecycleChange.value && wantsLog.value) {
        if (!logDraft.title.trim() || !logDraft.description.trim()) {
            logError.value =
                "Isi title dan description untuk menyimpan log (atau kosongkan keduanya untuk skip).";
            return;
        }

        form.log = {
            type: detectType(),
            title: logDraft.title.trim(),
            description: logDraft.description.trim(),
            feature_id: logDraft.feature_id || null,
        };
    } else {
        form.log = null;
    }

    if (props.method === "put") {
        form.put(props.submitUrl);
        return;
    }

    form.post(props.submitUrl);
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border p-8 space-y-6">
        <!-- Name -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">Name</label>
            <input
                v-model="form.name"
                class="w-full border rounded-lg px-4 py-2"
            />
            <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                {{ form.errors.name }}
            </div>
        </div>

        <!-- Status -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">Status</label>
            <select
                v-model="form.status"
                class="w-full border rounded-lg px-4 py-2"
            >
                <option value="active">{{ systemStatusLabel.active }}</option>
                <option value="maintenance">
                    {{ systemStatusLabel.maintenance }}
                </option>
                <option value="paused">{{ systemStatusLabel.paused }}</option>
                <option value="deprecated">
                    {{ systemStatusLabel.deprecated }}
                </option>
            </select>
            <div v-if="form.errors.status" class="text-sm text-red-600 mt-1">
                {{ form.errors.status }}
            </div>
        </div>

        <!-- Kind -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">System Type</label>
            <select
                v-model="form.kind"
                class="w-full border rounded-lg px-4 py-2"
            >
                <option value="app">{{ systemKindLabel.app }}</option>
                <option value="server">{{ systemKindLabel.server }}</option>
            </select>
            <div v-if="form.errors.kind" class="text-sm text-red-600 mt-1">
                {{ form.errors.kind }}
            </div>
        </div>

        <!-- Stage -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">
                Lifecycle Stage
            </label>
            <select
                v-model="form.stage"
                class="w-full border rounded-lg px-4 py-2"
            >
                <option value="">- (None)</option>
                <option value="planning">{{ systemStageLabel.planning }}</option>
                <option value="development">
                    {{ systemStageLabel.development }}
                </option>
                <option value="production">
                    {{ systemStageLabel.production }}
                </option>
                <option value="maintenance">
                    {{ systemStageLabel.maintenance }}
                </option>
            </select>
            <div v-if="form.errors.stage" class="text-sm text-red-600 mt-1">
                {{ form.errors.stage }}
            </div>
        </div>

        <!-- Released At -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">
                Released At
            </label>
            <DateField v-model="form.released_at" />
            <div
                v-if="form.errors.released_at"
                class="text-sm text-red-600 mt-1"
            >
                {{ form.errors.released_at }}
            </div>
        </div>

        <!-- Assisted Log Draft -->
        <div
            v-if="hasLifecycleChange"
            class="rounded-xl border bg-gray-50 p-6 space-y-4"
        >
            <div>
                <div class="font-semibold text-gray-900">
                    Perubahan lifecycle terdeteksi. Tambahkan log?
                </div>
                <div class="text-sm text-gray-500">
                    Opsional. Jika dikosongkan, perubahan system tetap tersimpan tanpa log.
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-500 mb-1">Type</label>
                    <input
                        :value="logDraft.type"
                        disabled
                        class="w-full border rounded-lg px-4 py-2 bg-white"
                    />
                </div>

                <div>
                    <label class="block text-sm text-gray-500 mb-1">
                        Feature (optional)
                    </label>
                    <select
                        v-model="logDraft.feature_id"
                        class="w-full border rounded-lg px-4 py-2 bg-white"
                    >
                        <option value="">(None - Global)</option>
                        <option
                            v-for="feature in features"
                            :key="feature.id"
                            :value="String(feature.id)"
                        >
                            {{ feature.title }}
                        </option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">Title</label>
                <input
                    v-model="logDraft.title"
                    class="w-full border rounded-lg px-4 py-2 bg-white"
                    placeholder="e.g. Status updated to maintenance"
                />
            </div>

            <div>
                <label class="block text-sm text-gray-500 mb-1">
                    Description
                </label>
                <textarea
                    v-model="logDraft.description"
                    rows="4"
                    class="w-full border rounded-lg px-4 py-2 bg-white"
                    placeholder="Ringkas apa yang berubah dan alasannya..."
                />
            </div>

            <div v-if="logError" class="text-sm text-red-600">
                {{ logError }}
            </div>
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
            <div
                v-if="form.errors.repository_url"
                class="text-sm text-red-600 mt-1"
            >
                {{ form.errors.repository_url }}
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm text-gray-500 mb-1">Description</label>
            <textarea
                v-model="form.description"
                rows="4"
                class="w-full border rounded-lg px-4 py-2"
            />
            <div
                v-if="form.errors.description"
                class="text-sm text-red-600 mt-1"
            >
                {{ form.errors.description }}
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between pt-6">
            <a
                :href="
                    isEdit
                        ? route('systems.show', system.slug)
                        : route('systems.index')
                "
                class="px-4 py-2 border rounded-lg"
            >
                Cancel
            </a>

            <button
                @click="submit"
                class="bg-black text-white px-6 py-2 rounded-lg"
                :disabled="form.processing"
            >
                {{ isEdit ? "Update System" : "Save" }}
            </button>
        </div>
    </div>
</template>
