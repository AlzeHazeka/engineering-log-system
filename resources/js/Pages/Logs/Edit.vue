<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import LogForm from "./Form.vue";

const props = defineProps({
    log: Object,
    systems: Array,
    referenceLogs: Array,
    selectedReferenceIds: Array,
    returnUrl: { type: String, default: null },
});

const submitUrl = props.returnUrl
    ? `${route("logs.update", props.log.id)}?return=${encodeURIComponent(
          props.returnUrl
      )}`
    : route("logs.update", props.log.id);
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 sm:py-8">
            <div class="max-w-4xl mx-auto px-4">
                <LogForm
                    :systems="systems"
                    :referenceLogs="referenceLogs"
                    :selectedReferenceIds="selectedReferenceIds"
                    :log="log"
                    :returnUrl="returnUrl"
                    method="put"
                    :submitUrl="submitUrl"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
