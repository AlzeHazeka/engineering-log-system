<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link } from "@inertiajs/vue3";
import { Users, AlarmClock, ChevronRight } from "lucide-vue-next";
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl space-y-6">
            <div>
                <div class="text-xl font-semibold text-slate-900">
                    Master Settings
                </div>
                <div class="text-sm text-slate-500">
                    Kelola data master & pengaturan admin.
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Link
                    :href="route('users.index')"
                    class="group rounded-xl border border-slate-200 bg-white p-5 hover:border-slate-300 hover:bg-slate-50 transition"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-[#AF4324]/10 text-[#AF4324] flex items-center justify-center"
                            >
                                <Users class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900">
                                    Users
                                </div>
                                <div class="mt-0.5 text-sm text-slate-500">
                                    Manage akun & akses user.
                                </div>
                            </div>
                        </div>
                        <ChevronRight
                            class="h-5 w-5 text-slate-400 group-hover:text-slate-600 transition"
                        />
                    </div>
                </Link>

                <Link
                    :href="route('settings.sla.index')"
                    class="group rounded-xl border border-slate-200 bg-white p-5 hover:border-slate-300 hover:bg-slate-50 transition"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="h-10 w-10 rounded-xl bg-[#AF4324]/10 text-[#AF4324] flex items-center justify-center"
                            >
                                <AlarmClock class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900">
                                    SLA
                                </div>
                                <div class="mt-0.5 text-sm text-slate-500">
                                    Atur durasi SLA untuk bug/fix.
                                </div>
                            </div>
                        </div>
                        <ChevronRight
                            class="h-5 w-5 text-slate-400 group-hover:text-slate-600 transition"
                        />
                    </div>
                </Link>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
