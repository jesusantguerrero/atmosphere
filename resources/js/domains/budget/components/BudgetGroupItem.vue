<script setup lang="ts">

import { computed, ref, onMounted, watchEffect }  from "vue";
import autoAnimate from "@formkit/auto-animate"
import { NDropdown } from "naive-ui";
import { router } from "@inertiajs/vue3";

import IconDrag from "@/Components/icons/IconDrag.vue";
import PointAlert from "@/Components/atoms/PointAlert.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import { ICategory, getCategoryLink } from "@/domains/transactions/models/transactions";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import ExpenseChartWidgetRow from "@/domains/transactions/components/ExpenseChartWidgetRow.vue";
import { format } from "date-fns";
import { inject } from "vue";
import axios from "axios";

const emit = defineEmits(['removed'])

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    forceExpanded: {
        type: Boolean,
        default: false
    },
    isMobile: {
        type: Boolean,
    },
    allowDrag: {
        type: Boolean,
    }
})

const isExpanded = ref(props.forceExpanded);
const toggleIcon = computed(() => isExpanded.value ? 'fa-chevron-up' : 'fa-chevron-down')
watchEffect(() => {
    if(props.forceExpanded) {
        isExpanded.value = true
    }
})

const isAdding = ref(false);
const toggleAdding = () => isAdding.value = !isAdding.value;

const dropdown = ref();
onMounted(() => {
    autoAnimate(dropdown.value)
})

const options = [{
    name: 'delete',
    label: 'Delete'
}, {
    name: 'transactions',
    label: 'Transactionss'
}]

const removeCategory = () => {
    if (confirm("Are you sure you want to remove this category group?")) {
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

const handleOptions = (option: any) => {
    switch(option) {
        case 'delete':
            removeCategory()
            break;
        case 'add':
            toggleAdding()
            break;
        case 'transactions':
            const link = getCategoryLink(props.item.id, 'groups');
            router.visit(link)
            break;
        default:
            break;
    }
}

const pageState = inject('pageState', {});
const details = ref("");
const fetchDetails = async (category: ICategory) => {
    const startDate = format(pageState.dates.startDate, 'yyyy-MM-dd');
    const endDate = format(pageState.dates.endDate, 'yyyy-MM-dd');

    const response = await axios.get(`/api/category-transactions/${category.id}/details`, {
        params: {
            filter: {
                date: `${startDate}~${endDate}`
            },
        }
    })

    details.value = response.data?.transactions.at(0).details;
}
</script>

<template>
<article>
    <header class="flex justify-between px-4 py-0.5 text-body-1/80 text-xs">
        <div class="flex items-center space-x-2">
            <div class="cursor-grab" v-if="isMobile && allowDrag">
                <IconDrag class="handle" />
            </div>
            <LogerButtonTab @click="isExpanded=!isExpanded" v-else>
                <i class="fa" :class="toggleIcon" />
            </LogerButtonTab>
            <div class="flex items-center">
                <h4 class="relative font-bold text-primary cursor-grab" :class="{'handle': !isMobile }">
                    {{ item.name }}
                    <PointAlert
                        v-if="item.hasOverspent || item.hasUnderfunded"
                    />
                </h4>
                <button class="ml-2 font-bold text-secondary" @click=" toggleAdding()">
                    <IMdiMinus v-if="isAdding" title="Add new category" />
                    <IMdiPlus  v-else />
                </button>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <MoneyPresenter :value="item.budgeted" />
            <ExpenseChartWidgetRow
                :value="item.activity"
                v-if="!isMobile"
                hide-title
                :item="item"
                type="groups"
                classes="w-44 h-full"
                :details="details"
                @open-details="fetchDetails(item)"
            />
            <MoneyPresenter :value="item.available" />
            <NDropdown
                trigger="click"
                key-field="name"
                :options="options"
                :on-select="handleOptions"
            >
                <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
            </NDropdown>
        </div>
    </header>
    <section class="border-l-4 border-primary bg-base-lvl-3" ref="dropdown">
        <slot v-if="isExpanded || isAdding" :isExpanded="isExpanded" :toggleAdding="toggleAdding" :isAdding="isAdding" name="content"/>
    </section>
</article>
</template>


