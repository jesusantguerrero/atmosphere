<template>
    <article class="relative flex pt-16 pb-20 mx-auto space-x-2 max-w-screen-2xl">
        <main class="w-full md:pr-5 md:w-10/12 md:pl-8">
            <slot />
        </main>

        <aside class="relative hidden h-screen px-2 overflow-auto md:block" :class="panelStyles">
            <section class="fixed aside-content">
                <slot name="panel">
                    <WidgetCard title="Accounts" class="mt-4">
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
                        class="px-4 py-2 space-y-4 mt-4 rounded-md cursor-pointer min-h-min bg-base-lvl-3"
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
    import { useTransactionModal } from '@/domains/transactions';
    import exactMathNode from 'exact-math';
    import { formatMoney } from '@/utils';
    import WidgetCard from '../molecules/WidgetCard.vue';
    import { useImportModal } from '@/domains/transactions/useImportModal';


    const { openTransactionModal, isOpen: isTransferModalOpen } = useTransactionModal();
    const { isOpen: isImportModalOpen, toggleModal: toggleImportModal } = useImportModal();

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
        return [sizes];
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
    padding-right: 32px;
}

</style>
