<script setup lang="ts">
import { format, startOfMonth } from 'date-fns';
import { inject } from 'vue';
import { router } from '@inertiajs/vue3';
import { NDropdown } from 'naive-ui';

import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import PointAlert from '@/Components/atoms/PointAlert.vue';

import { BudgetCategory } from '@/domains/budget/models/budget';

import { getCategoryLink } from '@/domains/transactions/models/transactions';


const props = defineProps<{
    item: BudgetCategory;
    showDelete: boolean;
}>();

const emit = defineEmits(['removed', 'edit'])


const options = [{
    name: 'delete',
    label: 'Delete'
}, {
    name: 'hide',
    label: 'Hide'
}, {
    name: 'updateActivity',
    label: 'Update Activity'
}]

const removeCategory = () => {
    if (confirm("Are you sure you want to remove this category?")) {
        router.delete(`budgets/${props.item.id}`, {
            onSuccess() {
                emit('removed', props.item.id)
                router.reload({
                    only: ['budgets'],
                    preserveScroll: true,
                })
            }
        })
    }
}

const pageState = inject('pageState', {});
const updateActivity = () => {
    const month = format(startOfMonth(pageState.dates.endDate), 'yyyy-MM-dd');

    router.put(`budgets/${props.item.id}/months/${month}`, {
        onSuccess() {
            router.reload({
                only: ['budgets'],
                preserveScroll: true,
            })
        }
    })
}

const handleOptions = (option: string) => {
    switch(option) {
        case 'delete':
            removeCategory()
            break;
        case 'updateActivity':
            updateActivity()
            break;
        case 'transactions':
            const link = getCategoryLink(props.item.id, 'categories');
            router.visit(link)
        default:
            break;
    }
}
</script>


<template>
<header class="flex group items-center justify-between w-56 h-6">
    <section class="flex items-center">
        <section class="hidden group-hover:block">
            <NDropdown trigger="click" :options="options" key-field="name" :on-select="handleOptions" >
                <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
            </NDropdown>
        </section>
        <h4 class="flex cursor-pointer ml-12 group-hover:ml-6 mb-2" @click="$emit('open')" :title="`ID: ${item.id}`">
            <span class="items-center font-bold text-body-1">
                <span :style="{ color: item.color }">
                    {{ item.name }}
                </span>
            </span>
            <PointAlert
                v-if="item.hasOverspent || item.hasUnderFunded"
            />
        </h4>
    </section>
    <button class="text-gray-400 hidden group-hover:inline-block transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', $event)">
        <i class="fa fa-trash"></i>
    </button>
</header>
</template>

