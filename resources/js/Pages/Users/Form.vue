<script setup>
import { computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import { ArrowLeft, Save } from "lucide-vue-next";

const props = defineProps({
    user: { type: Object, default: null },
    submitUrl: String,
    method: { type: String, default: "post" }, // post | put
});

const isEdit = computed(() => !!props.user);

const form = useForm({
    name: props.user?.name || "",
    email: props.user?.email || "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    if (props.method === "put") {
        form.put(props.submitUrl, {
            onFinish: () => form.reset("password", "password_confirmation"),
        });
        return;
    }

    form.post(props.submitUrl, {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xl font-semibold text-slate-900">
                    {{ isEdit ? "Edit User" : "New User" }}
                </div>
                <div class="text-sm text-slate-500">
                    Admin-only user provisioning.
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    :href="route('users.index')"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                    title="Back"
                    aria-label="Back"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 p-2 text-white hover:bg-slate-800 transition disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                    title="Save"
                    aria-label="Save"
                >
                    <Save class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div class="border border-slate-200 rounded-xl p-6 space-y-5">
            <div>
                <label class="block text-sm text-slate-600 mb-1">Name</label>
                <input
                    v-model="form.name"
                    class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                    type="text"
                />
                <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                    {{ form.errors.name }}
                </div>
            </div>

            <div>
                <label class="block text-sm text-slate-600 mb-1">Email</label>
                <input
                    v-model="form.email"
                    class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                    type="email"
                    autocomplete="off"
                />
                <div
                    v-if="form.errors.email"
                    class="text-sm text-red-600 mt-1"
                >
                    {{ form.errors.email }}
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">
                        Password
                        <span v-if="isEdit" class="text-xs text-slate-400"
                            >(optional)</span
                        >
                    </label>
                    <input
                        v-model="form.password"
                        class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                        type="password"
                        autocomplete="new-password"
                    />
                    <div
                        v-if="form.errors.password"
                        class="text-sm text-red-600 mt-1"
                    >
                        {{ form.errors.password }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">
                        Confirm Password
                    </label>
                    <input
                        v-model="form.password_confirmation"
                        class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                        type="password"
                        autocomplete="new-password"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

