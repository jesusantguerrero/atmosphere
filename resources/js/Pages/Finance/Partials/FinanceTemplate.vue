<script setup lang="ts">
    import { computed } from 'vue';
    import axios from 'axios';

    import AccountsLedger from "@/domains/transactions/components/AccountsLedger.vue";
    import { PANEL_SIZES } from '@/utils/constants';
    import { IAccount } from "@/domains/transactions/models";

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

    function saveReorder(items: IAccount[]) {
        const savedItems =  items?.reduce((accounts, account) => {
            accounts[account.id] = account;
            return accounts;
        }, {})
        axios.patch('/api/accounts/', { accounts: savedItems })
    }

    // Styling
    const panelStyles = computed(() => {
        const sizes = PANEL_SIZES[props.panelSize] || PANEL_SIZES.small;
        const visible = !props.forceShowPanel && 'hidden';
        return [sizes, visible];
    })
</script>

<template>
    <article class="relative flex flex-col-reverse w-full pt-16 pb-20 mx-auto md:flex-row md:space-x-2 md:max-w-screen-2xl">
        <main
            class="w-full overflow-hidden md:pr-5  md:pl-8"
            :class="!hidePanel && 'md:w-6/12 lg:w-7/12 xl:w-8/12 2xl:w-10/12'"
        >
            <slot />
        </main>

        <aside class="relative w-full overflow-auto md:px-2 md:block" :class="[panelStyles, fixed && 'h-screen']" v-if="!hidePanel">
            <section class="px-2 w-full  aside-content" :class="{'md:fixed md:pr-8': fixed }">
                <slot name="prepend-panel" />
                <slot name="panel">
                    <AccountsLedger
                        :accounts="accounts"
                        :class="[cardShadow]"
                        class="px-4 py-2 w-full space-y-4 cursor-pointer md:mt-4 rounded-b-md md:rounded-md min-h-min bg-base-lvl-3"
                        @reordered="saveReorder"
                    />
                </slot>
            </section>
        </aside>
    </article>
</template>
