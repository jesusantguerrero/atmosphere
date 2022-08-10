<template>
<div class="flex px-4 py-2 space-between">
    <div class="flex items-center w-full space-x-4">
        <button v-if="showDelete" class="text-gray-400 transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', $event)">
            <i class="fa fa-trash"></i>
        </button>
        <div class="mr-4 cursor-grab">
            <IconDrag class="handle" />
        </div>
        <div>
            <h4> {{ item.name }} </h4>
            <LogerInput v-model="budgeted" @blur="onAssignBudget" />
        </div>
    </div>
    <div class="flex items-center space-x-2 text-right flex-nowrap min-w-fit">
        <BalanceInput :value="item.available" :formatter="formatMoney" />
        <NDropdown trigger="click" :options="options" key-field="name" :on-select="handleOptions" >
            <LogerTabButton> <i class="fa fa-ellipsis-v"></i></LogerTabButton>
        </NDropdown>
    </div>
</div>
</template>

<script setup>
import { format, startOfMonth } from 'date-fns';
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { NDropdown } from 'naive-ui';

import IconDrag from '../icons/IconDrag.vue';
import BalanceInput from "@/Components/atoms/BalanceInput.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import LogerInput from '../atoms/LogerInput.vue';
import formatMoney from "@/utils/formatMoney";

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    showDelete: {
        type: Boolean,
        required: false,
    }
});

const emit = defineEmits(['removed'])
const budgeted = ref(props.item.budgeted);

const onAssignBudget = () => {
    if (Number(props.item.budgeted) !== Number(budgeted.value)) {
        const month = format(startOfMonth(new Date()), 'yyyy-MM-dd');
        Inertia.post(`/budgets/${props.item.id}/months/${month}`, {
            id: props.item.id,
            budgeted: budgeted.value
        }, {
            preserveState: true,
            preserveScroll: true
        });
    }
}


const options = [{
    name: 'delete',
    label: 'Delete'
}, {
    name: 'hide',
    label: 'Hide'
}]

const removeCategory = () => {
    if (confirm("Are you sure you want to remove this category?")) {
        Inertia.delete(`budgets/${props.item.id}`, {
            onSuccess() {
                emit('removed', props.item.id)
                Inertia.reload({
                    only: ['budgets'],
                    preserveScroll: true,
                    preserveState: true
                })
            }
        })
    }
}

const handleOptions = (option) => {
    switch(option) {
        case 'delete':
            removeCategory()
            break;
        default:
            break;
    }
}
</script>
