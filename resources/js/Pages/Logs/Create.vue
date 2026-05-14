<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import LogForm from "./Form.vue";

const props = defineProps({
    systems: Array,
    referenceLogs: Array,
    selectedReferenceIds: Array,
    log: Object,
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
                    :log="null"
                    method="post"
                    :submitUrl="route('logs.store')"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
