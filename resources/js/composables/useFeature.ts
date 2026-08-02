import { computed, type ComputedRef } from "vue";
import { usePage } from "@inertiajs/vue3";

/**
 * useFeature — frontend companion to the backend Feature facade.
 *
 * Reads the `featureFlags` shared prop that HandleInertiaRequests
 * populates on every response. Returns a reactive computed so components
 * re-render when Inertia navigates and the flag payload changes.
 *
 * Shape of the shared prop: only ACTIVE flags are included, so a missing
 * key means the flag is off (or doesn't exist yet).
 *
 * Usage in a script setup:
 *   const trendsRelationships = useFeature('trends-relationships');
 *   // then in template:  <RelationshipsTab v-if="trendsRelationships" />
 *
 * For a one-off boolean without needing reactivity:
 *   if (isFeatureActive('trends-relationships')) { ... }
 */
export function useFeature(key: string): ComputedRef<boolean> {
    const page = usePage();

    return computed<boolean>(() => {
        const flags = (page.props as Record<string, unknown>).featureFlags as
            | Record<string, boolean>
            | undefined;

        return Boolean(flags?.[key]);
    });
}

/**
 * Non-reactive variant for one-off checks (e.g. in a click handler where
 * `useFeature` inside a function would fail with "outside setup" errors).
 * Trades reactivity for portability — use `useFeature` when the value can
 * change during the component's lifetime.
 */
export function isFeatureActive(key: string): boolean {
    const page = usePage();
    const flags = (page.props as Record<string, unknown>).featureFlags as
        | Record<string, boolean>
        | undefined;

    return Boolean(flags?.[key]);
}
