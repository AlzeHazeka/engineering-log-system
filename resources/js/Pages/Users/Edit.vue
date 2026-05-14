<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UserForm from "./Form.vue";

const props = defineProps({
    user: Object,
});
</script>

<template>
    <AuthenticatedLayout>
        <UserForm
            :user="user"
            method="put"
            :submitUrl="route('users.update', user.id)"
        />
    </AuthenticatedLayout>
</template>

