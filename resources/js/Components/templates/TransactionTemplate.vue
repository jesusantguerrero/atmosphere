<template>
    <div class="pb-20 mt-5">
        <CustomTable
            :cols="tableCols"
            :show-prepend="true"
            :table-data="transactions"
            @edit="handleEdit"
        >
            <template v-slot:total="{ scope: { row } }">
                <div class="font-bold" :class="{'text-red-400':  row.direction == 'WITHDRAW', 'text-green-500': row.direction == 'DEPOSIT'}">
                    {{ formatMoney(row.total, row.currency_code) }}
                </div>
            </template>
            <template v-slot:actions="{ scope: { row } }">
               <div>
                 <NDropdown
                    trigger="click"
                    key-field="name"
                    :options="options"
                    :on-select="handleOptions"
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


    const options = computed(() => {
        const defaultOptions = [
            {
            name: "edit",
            label: "Edit",
            },
            {
            name: "removed",
            label: "Remove",
            },
        ];

    return defaultOptions.filter(option => !option.hide)
    });

    const handleOptions = (option) => {
        emit(option);
    };
</script>
