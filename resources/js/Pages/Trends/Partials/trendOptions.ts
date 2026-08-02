import { computed, type ComputedRef } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface TrendSubTab {
    label: string;
    url: string;
}

export interface TrendOption {
    label: string;
    url?: string;
    subTabs?: TrendSubTab[];
    /**
     * Optional feature-flag gate. When set, the option only appears if
     * the flag is active for the current user context (per-user > per-
     * team > global). Wire from the Admin Feature Flags panel to
     * toggle without a code deploy.
     */
    featureFlag?: string;
}

/**
 * Raw definition — includes every option regardless of flag state.
 * Kept exported for tests and for admin UIs that want the full catalog.
 * Consumers of the nav should use `useTrendOptions()` instead so
 * feature-flag filtering is applied.
 */
export const trendOptionsRaw: TrendOption[] = [
    {
        label: 'Insights',
        url: '/trends',
    },
    {
        label: 'Net Worth',
        url: '/trends/net-worth',
    },
    {
        label: 'Credit Cards',
        url: '/trends/credit-cards',
    },
    {
        label: 'Financial Overview',
        url: '/trends/financial-overview',
    },
    {
        label: 'Relationships',
        url: '/trends/relationships',
        featureFlag: 'trends-relationships',
    },
];

/**
 * Reactive, flag-filtered trend options. Reads `featureFlags` from the
 * Inertia page props on every access so an admin toggle propagates on
 * the next navigation without a page reload of every open tab.
 */
export function useTrendOptions(): ComputedRef<TrendOption[]> {
    const page = usePage();

    return computed(() => {
        const flags = (page.props as Record<string, unknown>).featureFlags as
            | Record<string, boolean>
            | undefined;

        return trendOptionsRaw.filter((opt) => {
            if (!opt.featureFlag) return true;
            return Boolean(flags?.[opt.featureFlag]);
        });
    });
}

/**
 * Backwards-compat alias. New code should call `useTrendOptions()` for
 * flag-aware filtering; this static export ignores flags entirely.
 * @deprecated
 */
export const trendOptions: TrendOption[] = trendOptionsRaw;
