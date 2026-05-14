<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UserForm from "./Form.vue";
</script>

<template>
    <AuthenticatedLayout>
        <UserForm :user="null" method="post" :submitUrl="route('users.store')" />
    </AuthenticatedLayout>
</template>

