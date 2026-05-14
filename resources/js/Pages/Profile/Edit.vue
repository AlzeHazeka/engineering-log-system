<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div>
                <div class="text-xl font-semibold text-slate-900">Profile</div>
                <div class="text-sm text-slate-500">
                    Manage your account details and security.
                </div>
            </div>

            <div class="border border-slate-200 rounded-xl p-6">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="border border-slate-200 rounded-xl p-6">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="border border-slate-200 rounded-xl p-6">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
