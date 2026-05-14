<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LoginInfoPanel from './_LoginInfoPanel.vue';
import BrandLogo from "@/Components/BrandLogo.vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen bg-slate-50 p-6 sm:p-10 flex items-center justify-center">
        <div class="w-full max-w-5xl grid lg:grid-cols-2 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="hidden lg:block">
                <LoginInfoPanel />
            </div>

            <div class="p-6 sm:p-10">
                <div class="max-w-md mx-auto">
                    <!-- Mobile brand header -->
                    <div class="lg:hidden mb-6 flex items-center gap-3">
                        <div class="h-11 w-11 rounded-xl bg-[#AF4324] flex items-center justify-center">
                            <BrandLogo variant="white" sizeClass="h-6 w-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900 truncate">
                                System Management Workstation
                            </div>
                            <div class="text-xs text-slate-500 truncate">
                                System management timeline & reporting
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8"
                    >
                        <div class="mb-6">
                            <div class="text-2xl font-semibold text-slate-900">
                                Login
                            </div>
                            <div class="mt-1 text-sm text-slate-500">
                                Sign in to continue.
                            </div>
                        </div>

                    <div
                        v-if="status"
                        class="mb-4 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2"
                    >
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <InputLabel for="email" value="Email" />

                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />

                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Password" />

                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />

                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="ms-2 text-sm text-slate-600">
                                    Remember me
                                </span>
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm text-slate-600 hover:text-slate-900 underline underline-offset-4"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <PrimaryButton
                            class="w-full justify-center bg-[#AF4324] hover:bg-[#93371D] focus:bg-[#93371D]"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Sign in
                        </PrimaryButton>
                    </form>
                    </div>

                    <div class="mt-6 text-xs text-slate-500">
                        No registration. Accounts are created by admin.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
