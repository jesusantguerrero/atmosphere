<template>
    <article class="relative flex-col-reverse w-full md:flex-row flex pt-16 pb-20 mx-auto md:space-x-2 md:max-w-screen-2xl">
        <main class="w-full md:pr-5 md:w-10/12 md:pl-8">
            <slot />
        </main>

        <aside class="relative h-screen w-full md:px-2 overflow-auto md:block" :class="panelStyles">
            <section class="md:fixed aside-content px-2 md:pr-8">
                <slot name="prepend-panel" />
                <slot name="panel">
                    <WidgetCard title="Accounts" class="mt-4 rounded-t-md">
                        <template #subtitle>
                            <LogerTabButton class="flex items-center text-primary" @click="toggleImportModal()" title="import">
                                {{ formatMoney(budgetAccountsTotal) }}
                                <IconImport />
                            </LogerTabButton>
                        </template>
                    </WidgetCard>
                    <AccountsLedger
                        :accounts="accounts"
                        @reordered="saveReorder"
                        :class="[cardShadow]"
                        class="px-4 py-2 space-y-4 md:mt-4 rounded-b-md md:rounded-md cursor-pointer min-h-min bg-base-lvl-3"
                    />
                </slot>
            </section>
        </aside>
    </article>
</template>

<script setup>
    import { computed } from 'vue';
    import AccountsLedger from "@/Components/templates/AccountsLedger.vue";
    import { PANEL_SIZES } from '@/utils/constants';
    import IconImport from '@/Components/icons/IconImport.vue';
    import LogerTabButton from '@/Components/atoms/LogerTabButton.vue';
    import exactMathNode from 'exact-math';
    import { formatMoney } from '@/utils';
    import WidgetCard from '../molecules/WidgetCard.vue';
    import { useImportModal } from '@/domains/transactions/useImportModal';

    const { toggleModal: toggleImportModal } = useImportModal();

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
        const visible = !props.forceShowPanel && 'hidden';
        return [sizes, visible];
    })


    const budgetAccountsTotal =  computed(() => {
        return props.accounts.reduce((total, account) => {
            return exactMathNode.add(total, account?.balance)
        }, 0)
    })
</script>


<style lang="scss" scoped>
.aside-content {
    width: -webkit-fill-available;
}

</style>
