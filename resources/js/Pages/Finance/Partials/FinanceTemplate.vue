<script setup lang="ts">
    import { computed, useSlots } from 'vue';

    import AccountsLedger from "@/domains/transactions/components/AccountsLedger.vue";
    import { saveAccountsReorder } from "@/domains/transactions";
    import { PANEL_SIZES } from '@/utils/constants';
    import { useAppContextStore } from "@/store";

    const props = defineProps({
        title: {
            type: String
        },
        categories: {
            type: Array,
            default() {
                return []
            }
        },
        accounts: {
            type: Array,
            default() {
                return []
            }
        },
        panelSize: {
            type: String,
            validator(value: string) {
                return Object.keys(PANEL_SIZES).includes(value)
            }
        },
        forceShowPanel: {
            type: Boolean,
        },
        fixed: {
            type: Boolean,
            default: true,
        },
        hidePanel: {
            type: Boolean
        }
    });

    const slots = useSlots();
    const context = useAppContextStore();

    // The accounts ledger used to be this template's default panel on every
    // finance page. It now lives in the right-rail widget (FinanceWidget), which
    // is desktop-only — so the fallback survives on mobile, where the rail is
    // hidden, and desktop pages get the width back.
    const showAccountsFallback = computed(() => context.isMobile);
    // Not a computed: `slots` is not reactive, so a cached computed would go
    // stale when a page toggles its panel slot (e.g. Transactions' mobile-only
    // prepend-panel). Evaluated on every render instead.
    const showPanel = () => {
        const hasPanelContent = Boolean(slots.panel || slots['prepend-panel']);
        return !props.hidePanel && (hasPanelContent || showAccountsFallback.value);
    };

    // Styling
    const panelStyles = computed(() => {
        const sizes = PANEL_SIZES[props.panelSize] || PANEL_SIZES.small;
        const visible = !props.forceShowPanel && 'hidden';
        return [sizes, visible];
    })
</script>

<template>
    <article class="px-3 sm:px-5 mx-auto mt-12 space-y-6 md:space-y-0 md:space-x-10  relative md:flex max-w-screen-2xl sm:px-6 lg:px-8">
        <main
            class="overflow-hidden md:pr-5 md:pl-8"
            :class="showPanel() ? 'md:w-6/12 lg:w-7/12 xl:w-8/12 2xl:w-10/12' : 'md:w-full'"
        >
            <slot name="prepend-panel" v-if="hidePanel" />
            <slot />
        </main>

        <aside class="space-y-4 md:w-3/12 md:sticky md:top-0" v-if="showPanel()">
            <section class="w-full md:px-2 aside-content">
                <slot name="prepend-panel" />
                <slot name="panel">
                    <AccountsLedger
                        v-if="showAccountsFallback"
                        :accounts="accounts"
                        class="px-4 py-2 w-full space-y-4 cursor-pointer md:mt-4 rounded-md min-h-min bg-base-lvl-3"
                        @reordered="saveAccountsReorder"
                    />
                </slot>
            </section>
        </aside>
    </article>
</template>
