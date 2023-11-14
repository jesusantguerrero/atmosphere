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
    <article class="relative flex flex-col-reverse w-full pt-16 pb-20 mx-auto md:flex-row md:space-x-2 md:max-w-screen-2xl">
        <main class="w-full overflow-hidden md:pr-5 md:w-7/12 lg:w-10/12 md:pl-8">
            <slot />
        </main>

        <aside class="relative w-full h-screen overflow-auto md:px-2 md:block" :class="panelStyles" v-if="!hidePanel">
            <section class="px-2 md:fixed aside-content md:pr-8">
                <slot name="prepend-panel" />
                <slot name="panel">
                    <AccountsLedger
                        :accounts="accounts"
                        :class="[cardShadow]"
                        class="px-4 py-2 space-y-4 cursor-pointer md:mt-4 rounded-b-md md:rounded-md min-h-min bg-base-lvl-3"
                        @reordered="saveReorder"
                    />
                </slot>
            </section>
        </aside>
    </article>
</template>




<style lang="scss" scoped>
.aside-content {
    width: -webkit-fill-available;
}
</style>
