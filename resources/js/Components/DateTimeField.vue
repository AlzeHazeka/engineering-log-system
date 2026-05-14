<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: [String, null], default: null }, // YYYY-MM-DD HH:mm:ss
    placeholder: { type: String, default: "Pilih tanggal & waktu" },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const model = computed({
    get: () => props.modelValue || null,
    set: (value) => emit("update:modelValue", value || null),
});
</script>

<template>
    <VueDatePicker
        v-model="model"
        model-type="yyyy-MM-dd HH:mm:ss"
        format="yyyy-MM-dd HH:mm"
        :enable-time-picker="true"
        :is-24="true"
        :time-config="{ is24: true }"
        :clearable="true"
        :auto-apply="true"
        :disabled="disabled"
        :placeholder="placeholder"
        class="w-full"
    />
</template>
