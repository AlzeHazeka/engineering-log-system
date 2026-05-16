<script setup>
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: "Konfirmasi" },
    description: { type: String, default: "" },
    confirmText: { type: String, default: "Ya, lanjutkan" },
    cancelText: { type: String, default: "Batal" },
    tone: {
        type: String,
        default: "danger", // danger | primary
    },
    confirmDisabled: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "confirm"]);

const confirmClass =
    props.tone === "primary"
        ? "border-[#AF4324] bg-[#AF4324] text-white hover:opacity-95"
        : "border-red-600 bg-red-600 text-white hover:opacity-95";
</script>

<template>
    <Modal :show="show" maxWidth="lg" @close="emit('close')">
        <div class="p-6">
            <div class="text-lg font-semibold text-slate-900">
                {{ title }}
            </div>
            <div v-if="description" class="mt-2 text-sm text-slate-600">
                {{ description }}
            </div>

            <div v-if="$slots.default" class="mt-4">
                <slot />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                    @click="emit('close')"
                >
                    {{ cancelText }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-medium transition"
                    :class="confirmClass"
                    :disabled="confirmDisabled"
                    @click="emit('confirm')"
                >
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>

