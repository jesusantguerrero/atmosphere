<template>
    <div class="pb-20 mt-5 bg-base-lvl-3">
        <CustomTable
            :cols="tableCols"
            :show-prepend="true"
            :table-data="transactions"
            @edit="handleEdit"
        >
            <template v-slot:total="{ scope: { row } }">
                <div class="font-bold" :class="[getTransactionColor(row)]">
                    {{ formatMoney(row.total, row.currency_code) }}
                </div>
            </template>

            <template v-slot:description="{ scope: { row } }">
                <span class="capitalize text-xs">
                    {{ row.description }}
                    {{ row.linked }}
                </span>
            </template>

            <template v-slot:actions="{ scope: { row } }">
               <div>
                 <NDropdown
                    trigger="click"
                    key-field="name"
                    :options="options(row)"
                    :on-select="(optionName) => handleOptions(optionName, row)"
                    @click.stop
                >
                <button class="hover:bg-base-lvl-3 px-2"> <i class="fa fa-ellipsis-v"></i></button>
                </NDropdown>
                </div>
            </template>
        </CustomTable>
    </div>
</template>

<script setup>
    import { reactive, ref, computed } from "vue";
    import { NDropdown } from "naive-ui";

    import CustomTable from "@/Components/atoms/CustomTable.vue";

    import { tableCols } from '@/domains/transactions';
    import formatMoney from '@/utils/formatMoney';

    const props = defineProps({
        transactions: {
            type: Array,
            default: () => []
        },
        serverSearchOptions: {
            type: Object,
            default: () => ({})
        },
        title: {
            type: String,
            default: 's'
        }
    })

    const emit = defineEmits(['removed', 'edit', 'approved'])

    const isTransferModalOpen = ref(false);
    const transferConfig = reactive({
        recurrence: false,
        automatic: false,
        transactionData: null
    })

    const handleEdit = (transaction) => {
        transferConfig.transactionData = transaction;
        isTransferModalOpen.value = true;
    }

    const options = (row) => {
        const defaultOptions = [
            {
                name: "edit",
                label: "Edit",
            },
            {
                name: "removed",
                label: "Remove",
            },
            {
                name: 'findLinked',
                label: 'Find Linked',
                hide: row.status != 'draft'
            }
        ];

        return defaultOptions.filter(option => !option.hide)
    };

    const handleOptions = (option, transaction) => {
        emit(option, transaction);
    };

    const getTransactionColor = (row) => {
        if (row.payee?.name) {
            return row.direction == 'WITHDRAW' ? 'text-red-400':  'text-green-500';
        }
        return 'text-body-1';
    }
</script>
