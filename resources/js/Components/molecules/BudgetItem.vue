<template>
<div class="flex px-4 py-2 space-between">
    <div class="flex items-center w-full space-x-4">
        <button v-if="showDelete" class="text-gray-400 transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', $event)">
            <i class="fa fa-trash"></i>
        </button>
        <div class="mr-4 cursor-grab">
            <IconDrag class="handle" />
        </div>
        <div ref="inputContainer">
            <h4 class="cursor-pointer flex" @click="$emit('open')">
                <span class="items-center text-body-1 font-bold">
                    <span :style="{ color: item.color }">
                        {{ item.name }}
                    </span>
                    <i class="fa fa-cog ml-2" @click.stop="$emit('edit')"></i>
                </span>
                <PointAlert
                    v-if="item.hasOverspent || item.hasOverAssigned || item.hasUnderFunded"
                />
            </h4>
            <div class="flex items-center" title="Money Assigned">
                <IconAllocated class="text-lg text-success" />
                <span v-if="!isEditing" @click="toggleEditing" class="border border-transparent px-4 rounded-md hover:border-slate-400 cursor-pointer py-2.5 inline-block transition hover:text-primary">
                    {{ formatMoney(budgeted) }}
                </span>
                <LogerInput v-model="budgeted" @blur="onAssignBudget" :number-format="true" v-else ref="input" />
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-2 text-right flex-nowrap min-w-fit">
        <BalanceInput
            :value="item.available"
            :formatter="formatMoney"
            :category="item"
        >
            <template #suffix v-if="item.available">
                <BudgetTransaction
                    :data="item.budget"
                    :category="item"
                    icon-only
                />
            </template>
        </BalanceInput>
        <NDropdown trigger="click" :options="options" key-field="name" :on-select="handleOptions" >
            <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
        </NDropdown>
    </div>
</div>
</template>

<script setup>
import { format, startOfMonth } from 'date-fns';
import { ref, nextTick, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { NDropdown } from 'naive-ui';

import IconDrag from '../icons/IconDrag.vue';
import BalanceInput from "@/Components/atoms/BalanceInput.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import LogerInput from '../atoms/LogerInput.vue';
import formatMoney from "@/utils/formatMoney";
import autoAnimate from '@formkit/auto-animate';
import BudgetTransaction from '../atoms/BudgetTransaction.vue';
import IconAllocated from '../icons/IconAllocated.vue';
import PointAlert from '../atoms/PointAlert.vue';

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

const emit = defineEmits(['removed', 'edit'])
const budgeted = ref(props.item.budgeted);

const onAssignBudget = () => {
    if (Number(props.item.budgeted) !== Number(budgeted.value)) {
        const month = format(startOfMonth(new Date()), 'yyyy-MM-dd');
        Inertia.post(`/budgets/${props.item.id}/months/${month}`, {
            id: props.item.id,
            budgeted: Number(budgeted.value)
        }, {
            preserveState: true,
            preserveScroll: true
        });
    }
    toggleEditing()
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

const isEditing = ref(false);
const input = ref()
const toggleEditing = () => {
    isEditing.value = !isEditing.value
    if (isEditing.value) {
        nextTick(() => {
            input.value.focus()
        })
    }
}

const inputContainer = ref()
onMounted(() => {
    autoAnimate(inputContainer.value)
})
</script>
