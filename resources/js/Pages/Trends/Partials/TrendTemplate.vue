<script setup lang="ts">
    import { computed } from 'vue';

    import AccountsLedger from "@/domains/transactions/components/AccountsLedger.vue";

    import { useImportModal } from '@/domains/transactions/useImportModal';
    import { PANEL_SIZES } from '@/utils/constants';

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
            validator(value) {
                return Object.keys(PANEL_SIZES).includes(value)
            }
        },
        forceShowPanel: {
            type: Boolean,
        },
        hidePanel: {
            type: Boolean,
        }
    });

    function saveReorder(items) {
        const savedItems =  items?.reduce((accounts, account) => {
            accounts[account.id] = account;
            return accounts;
        }, {})
        axios.patch('/api/accounts/', { accounts: savedItems })
    }

    // Styling
    const panelStyles = computed(() => {
        const sizes = PANEL_SIZES[props.panelSize] || PANEL_SIZES.small;
        const visible = (!props.forceShowPanel || props.hidePanel) && 'hidden';
        return [sizes, visible];
    })
</script>

<template>
    <article class="relative px-3 pt-16 pb-20 mx-auto space-y-6 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
        <main
            class="overflow-hidden md:pr-5 md:pl-8"
            :class="hidePanel ? 'md:w-full' : 'md:w-6/12 lg:w-7/12 xl:w-8/12 2xl:w-10/12'"
        >
            <slot />
        </main>

        <aside class="space-y-4 md:w-3/12" v-if="!hidePanel">
            <section class="w-full md:px-2 aside-content">
                <slot name="prepend-panel" />
                <slot name="panel">
                    <AccountsLedger
                        :accounts="accounts"
                        class="w-full px-4 py-2 space-y-4 cursor-pointer md:mt-4 rounded-md min-h-min bg-base-lvl-3"
                        @reordered="saveReorder"
                    />
                </slot>
            </section>
        </aside>
    </article>
</template>
