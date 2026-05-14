<script setup>
import BrandLogo from "@/Components/BrandLogo.vue";
</script>

<template>
    <div
        class="relative h-full w-full bg-[#AF4324] text-white p-10 flex flex-col justify-between"
    >
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <div
                    class="h-12 w-12 rounded-xl bg-white/10 flex items-center justify-center"
                >
                    <BrandLogo variant="white" sizeClass="h-7 w-7" />
                </div>
                <div class="min-w-0">
                    <div class="text-xl font-semibold leading-tight">
                        System Management Workstation
                    </div>
                    <div class="text-sm text-white/80">
                        System management timeline & reporting
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="text-sm font-semibold">What is this?</div>
                <p class="text-sm text-white/85 leading-relaxed">
                    Internal workstation to track system management work through
                    <span class="font-medium">Logs</span> (source of truth),
                    grouped by <span class="font-medium">Systems</span> and
                    optional <span class="font-medium">Features</span>.
                </p>
                <p class="text-sm text-white/85 leading-relaxed">
                    Designed for weekly reporting, SLA visibility, and clean
                    audit-friendly timelines.
                </p>
            </div>

            <div class="space-y-2">
                <div class="text-sm font-semibold">Key concepts</div>
                <ul class="text-sm text-white/85 space-y-1">
                    <li>- System → top level (app/web or server)</li>
                    <li>- Feature → optional grouping</li>
                    <li>- Logs → progress, bug, fix, decision</li>
                </ul>
            </div>
        </div>

        <div class="text-xs text-white/70">
            Access is restricted. Contact admin for credentials.
        </div>
    </div>
</template>
