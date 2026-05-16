<script setup>
import { computed, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import BrandLogo from "@/Components/BrandLogo.vue";

import {
    Home,
    Layers,
    FileText,
    Download,
    Users,
    UserRound,
    LogOut,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    Settings2,
} from "lucide-vue-next";

const page = usePage();

const mobileOpen = ref(false);
const desktopCollapsed = ref(false); // optional: collapse on desktop (lg+)

const showLabels = computed(() => !desktopCollapsed.value);

const currentUrl = computed(() => page.url?.value ?? page.url ?? "");
const isReportView = computed(() => {
    if (!route().current("reports.*")) return false;
    const qs = currentUrl.value.split("?")[1] ?? "";
    return new URLSearchParams(qs).get("report_view") === "1";
});

const navItems = computed(() => [
    {
        key: "dashboard",
        label: "Dashboard",
        href: route("dashboard"),
        active: route().current("dashboard"),
        icon: Home,
    },
    {
        key: "systems",
        label: "Systems",
        href: route("systems.index"),
        active: route().current("systems.*"),
        icon: Layers,
    },
    {
        key: "logs",
        label: "Logs",
        href: route("logs.index"),
        active: route().current("logs.*"),
        icon: FileText,
    },
    {
        key: "exports",
        label: "Exports",
        href: route("reports.index"),
        active: route().current("reports.*"),
        icon: Download,
    },
    ...(page.props.can?.manage_users
        ? [
              {
                  key: "master_settings",
                  label: "Master Settings",
                  href: route("settings.master"),
                  active:
                      route().current("settings.*") ||
                      route().current("users.*"),
                  icon: Settings2,
              },
          ]
        : []),
]);

const closeMobile = () => {
    mobileOpen.value = false;
};

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Report view: full width, hide navigation chrome -->
        <div v-if="isReportView" class="min-h-screen bg-white">
            <main class="p-6 sm:p-8">
                <div class="mx-auto max-w-none">
                    <slot />
                </div>
            </main>
        </div>

        <template v-else>
        <!-- Mobile drawer overlay -->
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-40 bg-black/40 md:hidden"
            @click="closeMobile"
        />

        <!-- Mobile drawer -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-[#AF4324] text-white md:hidden transform transition-transform duration-200 ease-out"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="h-full flex flex-col">
                <div
                    class="p-4 flex items-center gap-3 border-b border-white/10"
                >
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center gap-3 min-w-0"
                        @click="closeMobile"
                    >
                        <BrandLogo variant="white" sizeClass="h-8 w-8" />
                        <div class="min-w-0">
                            <div class="text-sm font-semibold truncate">
                                System Workstation
                            </div>
                            <div class="text-xs text-white/70 truncate">
                                System Management RAI
                            </div>
                        </div>
                    </Link>
                </div>

                <nav class="p-2 space-y-1 flex-1 overflow-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            item.active
                                ? 'bg-[#7A2E18] text-white'
                                : 'text-white/90 hover:bg-[#93371D] hover:text-white'
                        "
                        @click="closeMobile"
                    >
                        <component :is="item.icon" class="h-5 w-5 shrink-0" />
                        <span class="truncate">{{ item.label }}</span>
                    </Link>
                </nav>

                <div class="p-3 border-t border-white/10 space-y-2">
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-[#93371D] transition"
                        @click="closeMobile"
                    >
                        <div
                            class="h-9 w-9 rounded-lg bg-white/10 flex items-center justify-center"
                        >
                            <UserRound class="h-5 w-5 text-white/90" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate">
                                {{ page.props.auth.user.name }}
                            </div>
                            <div class="text-xs text-white/70 truncate">
                                {{ page.props.auth.user.email }}
                            </div>
                        </div>
                    </Link>
                    <button
                        type="button"
                        class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-[#93371D] hover:text-white transition"
                        @click="logout"
                    >
                        <LogOut class="h-5 w-5 shrink-0" />
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex">
            <!-- Desktop/tablet sidebar -->
            <aside
                class="hidden md:fixed md:inset-y-0 md:left-0 md:z-30 md:flex md:flex-col bg-[#AF4324] text-white border-r border-white/10 transition-[width] duration-200 ease-out md:w-16"
                :class="desktopCollapsed ? 'lg:w-16' : 'lg:w-64'"
            >
                <div
                    class="p-4 flex items-center justify-center lg:justify-between border-b border-white/10"
                >
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center gap-3 min-w-0"
                        :class="showLabels ? '' : 'justify-center'"
                    >
                        <BrandLogo variant="white" sizeClass="h-8 w-8" />
                        <div v-if="showLabels" class="hidden lg:block min-w-0">
                            <div class="text-sm font-semibold truncate">
                                System Workstation
                            </div>
                            <div class="text-xs text-white/70 truncate">
                                System Management RAI
                            </div>
                        </div>
                    </Link>

                    <button
                        type="button"
                        class="hidden lg:inline-flex items-center justify-center rounded-lg p-2 text-white/90 hover:bg-[#93371D] hover:text-white transition"
                        @click="desktopCollapsed = !desktopCollapsed"
                        :title="desktopCollapsed ? 'Expand' : 'Collapse'"
                    >
                        <PanelLeftOpen
                            v-if="desktopCollapsed"
                            class="h-5 w-5"
                        />
                        <PanelLeftClose v-else class="h-5 w-5" />
                    </button>
                </div>

                <nav class="p-2 space-y-1 flex-1 overflow-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            item.active
                                ? 'bg-[#7A2E18] text-white'
                                : 'text-white/90 hover:bg-[#93371D] hover:text-white'
                        "
                        :title="item.label"
                    >
                        <component :is="item.icon" class="h-5 w-5 shrink-0" />
                        <span
                            v-if="showLabels"
                            class="hidden lg:inline truncate"
                        >
                            {{ item.label }}
                        </span>
                    </Link>
                </nav>

                <div class="p-3 border-t border-white/10 space-y-2">
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-[#93371D] transition"
                        :title="page.props.auth.user.name"
                    >
                        <div
                            class="h-9 w-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0"
                        >
                            <UserRound class="h-5 w-5 text-white/90" />
                        </div>
                        <div v-if="showLabels" class="hidden lg:block min-w-0">
                            <div class="text-sm font-medium truncate">
                                {{ page.props.auth.user.name }}
                            </div>
                            <div class="text-xs text-white/70 truncate">
                                {{ page.props.auth.user.email }}
                            </div>
                        </div>
                    </Link>
                    <button
                        type="button"
                        class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-[#93371D] hover:text-white transition"
                        @click="logout"
                        title="Logout"
                    >
                        <LogOut class="h-5 w-5 shrink-0" />
                        <span v-if="showLabels" class="hidden lg:inline">
                            Logout
                        </span>
                    </button>
                </div>
            </aside>

            <!-- Content -->
            <div
                class="flex-1 md:pl-16"
                :class="desktopCollapsed ? 'lg:pl-16' : 'lg:pl-64'"
            >
                <!-- Top bar -->
                <header
                    class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-slate-200"
                >
                    <div
                        class="px-4 sm:px-6 py-3 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex md:hidden items-center justify-center rounded-lg p-2 text-slate-700 hover:bg-slate-100 transition"
                                @click="mobileOpen = true"
                                title="Open menu"
                            >
                                <Menu class="h-5 w-5" />
                            </button>
                        </div>
                        <div />
                    </div>
                </header>

                <main class="p-4 sm:p-8">
                    <div class="mx-auto max-w-7xl">
                        <div
                            class="bg-white border border-slate-200 rounded-xl p-4 sm:p-8"
                        >
                            <slot />
                        </div>
                    </div>
                </main>
            </div>
        </div>
        </template>
    </div>
</template>
