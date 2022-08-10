<template>
    <div class="flex pb-20 mx-auto space-x-2 max-w-screen-2xl">
        <div class="w-full pr-5 md:w-10/12 md:pl-8">
            <slot />
        </div>

        <div class="hidden md:w-2/12 md:block">
            <SectionTitle class="flex items-center pl-5 mt-5" type="secondary">
                <span>
                    Budget Accounts
                </span>
                <LogerTabButton class="flex items-center ml-2 text-primary" @click="isImportModalOpen=!isImportModalOpen" title="import">
                    <IconImport />
                </LogerTabButton>
            </SectionTitle>
            <AccountsLedger :accounts="accounts" @reordered="saveReorder" class="mt-5" />
        </div>

        <TransactionModal v-bind="transferConfig" v-model:show="isTransferModalOpen" />
        <ImportResourceModal v-model:show="isImportModalOpen" />
    </div>
</template>

<script setup>
    import { provide, reactive, ref} from 'vue';
    import AccountsLedger from "@/Components/templates/AccountsLedger.vue";
    import SectionTitle from "@/Components/atoms/SectionTitle.vue";
    import TransactionModal from "@/Components/TransactionModal.vue"
    import { useSelect } from '@/utils/useSelects';
    import IconImport from '@/Components/icons/IconImport.vue';
    import LogerTabButton from '@/Components/atoms/LogerTabButton.vue';
    import ImportResourceModal from '@/Components/ImportResourceModal.vue';

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
        }
    });

    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(props.categories, 'accounts', 'categoryOptions');
    transformCategoryOptions(props.accounts, 'accounts', 'accountsOptions');

    // import modal related
    const isImportModalOpen = ref(false)


    // Transaction modal things
    const isTransferModalOpen = ref(false);
    const transferConfig = reactive({
        recurrence: false,
        automatic: false,
        transactionData: null
    })
    const openTransactionModal = (isRecurrent, automatic) => {
        isTransferModalOpen.value = true;
        transferConfig.recurrence = isRecurrent;
        transferConfig.automatic = automatic;
    }

    const openTransactionModalForEdit = (transaction) => {
        transferConfig.transactionData = transaction;
        isTransferModalOpen.value = true;
    }

    provide('openTransactionModal', openTransactionModal)
    provide('openTransactionModalForEdit', openTransactionModalForEdit)


    function saveReorder(items) {
        const savedItems =  items?.reduce((accounts, account) => {
            accounts[account.id] = account;
            return accounts;
        }, {})
        axios.patch('/api/accounts/', { accounts: savedItems })
    }

    defineExpose({
        openTransactionModal,
        openTransactionModalForEdit
    })
</script>
