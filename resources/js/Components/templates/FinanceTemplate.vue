<template>
    <div class="flex pb-20 pt-16 mx-auto space-x-2 max-w-screen-2xl relative">
        <div class="w-full pr-5 md:w-10/12 md:pl-8">
            <slot />
        </div>

        <div class="hidden md:block relative h-screen px-2" :class="panelStyles">
            <section class="fixed">
                <slot name="panel">
                    <SectionTitle class="flex items-center pl-5 mt-5" type="secondary">
                        <span>
                            Budget Accounts
                        </span>
                        <LogerTabButton class="flex items-center ml-2 text-primary" @click="isImportModalOpen=!isImportModalOpen" title="import">
                            <IconImport />
                        </LogerTabButton>
                    </SectionTitle>
                    <AccountsLedger :accounts="accounts" @reordered="saveReorder" class="mt-5" />
                </slot>
            </section>
        </div>

        <ImportResourceModal v-model:show="isImportModalOpen" />
    </div>
</template>

<script setup>
    import { provide, reactive, ref, computed } from 'vue';
    import AccountsLedger from "@/Components/templates/AccountsLedger.vue";
    import SectionTitle from "@/Components/atoms/SectionTitle.vue";
    import TransactionModal from "@/Components/TransactionModal.vue"
    import { useSelect } from '@/utils/useSelects';
    import { PANEL_SIZES } from '@/utils/constants';
    import IconImport from '@/Components/icons/IconImport.vue';
    import LogerTabButton from '@/Components/atoms/LogerTabButton.vue';
    import ImportResourceModal from '@/Components/ImportResourceModal.vue';
    import { useTransactionModal } from '@/domains/transactions';

    const { openTransferModal, isOpen: isTransferModalOpen } = useTransactionModal()

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

    // import modal related
    const isImportModalOpen = ref(false)

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

</script>
