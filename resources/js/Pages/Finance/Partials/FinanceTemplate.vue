<script setup lang="ts">
    import { computed } from 'vue';
    // @ts-ignore
    import exactMathNode from 'exact-math';
    
    import IconImport from '@/Components/icons/IconImport.vue';
    import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue';
    import WidgetCard from '@/Components/molecules/WidgetCard.vue';
    import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';
    
    import AccountsLedger from "@/domains/transactions/components/AccountsLedger.vue";
    
    import { useImportModal } from '@/domains/transactions/useImportModal';
    import { PANEL_SIZES } from '@/utils/constants';

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

<template>
    <article class="relative flex flex-col-reverse w-full pt-16 pb-20 mx-auto md:flex-row md:space-x-2 md:max-w-screen-2xl">
        <main class="w-full overflow-hidden md:pr-5 md:w-7/12 lg:w-10/12 md:pl-8">
            <slot />
        </main>

        <aside class="relative w-full h-screen overflow-auto md:px-2 md:block" :class="panelStyles">
            <section class="px-2 md:fixed aside-content md:pr-8">
                <slot name="prepend-panel" />
                <slot name="panel">
                    <WidgetCard title="Accounts" class="mt-4 rounded-t-md">
                        <template #subtitle>
                            <LogerButtonTab class="flex items-center w-full text-primary" @click="toggleImportModal()" title="import">
                                <MoneyPresenter :value="budgetAccountsTotal" class="mr-2" />
                                <IconImport />
                            </LogerButtonTab>
                        </template>
                    </WidgetCard>
                    <AccountsLedger
                        :accounts="accounts"
                        @reordered="saveReorder"
                        :class="[cardShadow]"
                        class="px-4 py-2 space-y-4 cursor-pointer md:mt-4 rounded-b-md md:rounded-md min-h-min bg-base-lvl-3"
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