<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { Plus, Pencil, Trash2 } from "lucide-vue-next";

const page = usePage();

const props = defineProps({
    users: Array,
    errors: Object,
});

const canManage = !!page.props.can?.manage_users;
const primaryAdminEmail = page.props.workstation?.primary_admin_email;

const destroyUser = (user) => {
    if (!confirm(`Delete user: ${user.name}?`)) return;
    router.delete(route("users.destroy", user.id), { preserveScroll: true });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xl font-semibold text-slate-900">
                        Users
                    </div>
                    <div class="text-sm text-slate-500">
                        Manage accounts (admin only).
                    </div>
                </div>

                <Link
                    v-if="canManage"
                    :href="route('users.create')"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 p-2 text-white hover:bg-slate-800 transition"
                    title="New user"
                    aria-label="New user"
                >
                    <Plus class="h-5 w-5" />
                </Link>
            </div>

            <div
                v-if="errors?.user"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ errors.user }}
            </div>

            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Created</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="hover:bg-slate-50"
                        >
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ user.name }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ user.email }}
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                {{
                                    new Date(user.created_at).toLocaleDateString(
                                        "id-ID"
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('users.edit', user.id)"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                                        title="Edit"
                                        aria-label="Edit"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 transition"
                                        title="Delete"
                                        aria-label="Delete"
                                        @click="destroyUser(user)"
                                        :disabled="user.email === primaryAdminEmail"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="(users ?? []).length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-10 text-center text-slate-500"
                            >
                                No users.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
