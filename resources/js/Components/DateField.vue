<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: { type: [String, null], default: null }, // YYYY-MM-DD
    placeholder: { type: String, default: "Pilih tanggal" },
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
        model-type="yyyy-MM-dd"
        format="yyyy-MM-dd"
        :enable-time-picker="false"
        :clearable="true"
        :auto-apply="true"
        :disabled="disabled"
        :placeholder="placeholder"
        class="w-full"
    />
</template>

