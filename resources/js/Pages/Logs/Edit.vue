<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import LogForm from "./Form.vue";

const props = defineProps({
    log: Object,
    systems: Array,
    referenceLogs: Array,
    selectedReferenceIds: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-8">
            <div class="max-w-4xl mx-auto px-4">
                <LogForm
                    :systems="systems"
                    :referenceLogs="referenceLogs"
                    :selectedReferenceIds="selectedReferenceIds"
                    :log="log"
                    method="put"
                    :submitUrl="route('logs.update', log.id)"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
