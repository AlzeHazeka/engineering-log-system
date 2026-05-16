<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import LogForm from "./Form.vue";

const props = defineProps({
    systems: Array,
    referenceLogs: Array,
    selectedReferenceIds: Array,
    log: Object,
    returnUrl: { type: String, default: null },
});

const submitUrl = props.returnUrl
    ? `${route("logs.store")}?return=${encodeURIComponent(props.returnUrl)}`
    : route("logs.store");
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 sm:py-8">
            <div class="max-w-4xl mx-auto px-4">
                <LogForm
                    :systems="systems"
                    :referenceLogs="referenceLogs"
                    :selectedReferenceIds="selectedReferenceIds"
                    :log="null"
                    :returnUrl="returnUrl"
                    method="post"
                    :submitUrl="submitUrl"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
