<script setup lang="ts">
import { format, startOfMonth } from 'date-fns';
import { computed, inject } from 'vue';
import { useI18n } from 'vue-i18n';
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

const emit = defineEmits(['removed', 'edit', 'open'])


const { t } = useI18n();

const options = computed(() => [{
    name: 'delete',
    label: t('Delete')
}, {
    name: 'hide',
    label: t('Hide')
}, {
    name: 'updateActivity',
    label: t('Update Activity')
}])

const removeCategory = () => {
    if (confirm(t('Are you sure you want to remove this category?'))) {
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

    router.put(`budgets/${props.item.id}/months/${month}`, {}, {
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
<header class="flex group items-center justify-between w-full md:w-56 min-w-0 h-6 relative">
    <section class="flex items-center min-w-0">
        <section class="hidden group-hover:block">
            <NDropdown trigger="click" :options="options" key-field="name" :on-select="handleOptions" >
                <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
            </NDropdown>
        </section>
        <h4 class="flex min-w-0 cursor-pointer md:ml-12 items-center md:group-hover:ml-6" @click="$emit('open')" :title="`ID: ${item.id}`">
            <span
                v-if="item.color"
                class="w-1.5 h-1.5 mr-2 shrink-0 rounded-full"
                :style="{ backgroundColor: item.color }"
                aria-hidden="true"
            />
            <span class="truncate font-bold text-body">
                {{ item.name }}
            </span>
            <PointAlert
                v-if="item.hasOverspent || item.hasUnderFunded"
                :color="item.hasOverspent ? 'error' : 'warning'"
                :title="item.hasOverspent ? $t('Overspent') : $t('Underfunded')"
            />
            <span
                v-if="item.description"
                class="ml-1 text-body-1/40"
                :title="item.description"
            >
                <IMdiNoteOutline class="text-xs" />
            </span>
        </h4>
    </section>
    <button class="text-body-1/70 hidden group-hover:inline-block transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', $event)">
       <IMdiTrash />
    </button>
</header>
</template>

