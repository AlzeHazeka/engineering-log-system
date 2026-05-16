import { onMounted } from "vue";
import { router } from "@inertiajs/vue3";

let lastNavWasPop = false;
let lastReloadAt = 0;

// Mark back/forward navigation (browser back button / history navigation).
if (typeof window !== "undefined") {
    window.addEventListener("popstate", () => {
        lastNavWasPop = true;
    });

    // BFCache restore handling
    window.addEventListener("pageshow", (e) => {
        if (e?.persisted) {
            lastNavWasPop = true;
        }
    });
}

/**
 * Soft revalidate Inertia props after a history navigation.
 *
 * Keeps:
 * - scroll position
 * - current query/pagination
 * - local UI state (preserveState)
 *
 * Refreshes:
 * - server props (only the requested props)
 */
export function useSoftRevalidate({ only = null } = {}) {
    onMounted(() => {
        if (!lastNavWasPop) return;

        const now = Date.now();
        // Avoid duplicate reloads when multiple components mount.
        if (now - lastReloadAt < 600) {
            lastNavWasPop = false;
            return;
        }

        lastReloadAt = now;
        lastNavWasPop = false;

        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: Array.isArray(only) ? only : undefined,
        });
    });
}

