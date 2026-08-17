<script setup lang="ts">

import { computed, ref, onMounted, watchEffect, watch }  from "vue";
import autoAnimate from "@formkit/auto-animate"
import { NDropdown } from "naive-ui";
import { router } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import IconDrag from "@/Components/icons/IconDrag.vue";
import PointAlert from "@/Components/atoms/PointAlert.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";

import { ICategory, getCategoryLink } from "@/domains/transactions/models/transactions";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import ExpenseChartWidgetRow from "@/domains/transactions/components/ExpenseChartWidgetRow.vue";
import { format } from "date-fns";
import { inject } from "vue";
import axios from "axios";

// Shared storage across all BudgetGroupItem instances. Keyed by group id so
// expanding "Vivienda" persists independently of "Comida". Survives reload.
const groupExpandedState = useStorage<Record<string, boolean>>('loger-budget-group-expanded', {});

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

// Initialize from persisted state if we have one for this group, otherwise fall back
// to forceExpanded (which the parent uses to e.g. expand all when filter is active).
const groupKey = computed(() => String(props.item.id));
const isExpanded = ref(
    groupExpandedState.value[groupKey.value] ?? props.forceExpanded
);
const toggleIcon = computed(() => isExpanded.value ? 'fa-chevron-up' : 'fa-chevron-down');

// Mirror state to storage on change.
watch(isExpanded, (open) => {
    groupExpandedState.value[groupKey.value] = open;
});

watchEffect(() => {
    if (props.forceExpanded) {
        isExpanded.value = true;
    }
});

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
    label: 'Transactions'
}]

const isConfirmingRemove = ref(false);

const requestRemove = () => {
    isConfirmingRemove.value = true;
};

const cancelRemove = () => {
    isConfirmingRemove.value = false;
};

const confirmRemove = () => {
    isConfirmingRemove.value = false;
    router.delete(`/budgets/${props.item.id}`, {
        onSuccess() {
            emit('removed', props.item.id);
            router.reload({
                only: ['budgets'],
                preserveScroll: true,
            });
        },
    });
};

const handleOptions = (option: any) => {
    switch (option) {
        case 'delete':
            requestRemove();
            break;
        case 'add':
            toggleAdding();
            break;
        case 'transactions':
            router.visit(getCategoryLink(props.item.id, 'groups'));
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
    <!--
        Desktop header: fixed column widths (w-36 / w-44 / w-28 / w-8) so the
        group totals align pixel-exact with (a) BudgetItem subcategory rows
        below and (b) the ASSIGNED / SPENT / AVAILABLE labels rendered in
        Budget.vue's row 2.
    -->
    <header v-if="!isMobile" class="flex justify-between px-4 py-0.5 text-body-1/80 text-xs">
        <div class="flex items-center space-x-2">
            <LogerButtonTab @click="isExpanded=!isExpanded">
                <i class="fa" :class="toggleIcon" />
            </LogerButtonTab>
            <div class="flex items-center">
                <h4 class="relative font-bold cursor-grab handle" :class="item.hasOverspent ? 'text-error' : 'text-body'">
                    {{ item.name }}
                    <PointAlert
                        v-if="item.hasOverspent"
                        :title="$t('Has overspent categories')"
                    />
                    <PointAlert
                        v-else-if="item.hasUnderfunded"
                        color="warning"
                        :title="$t('Has underfunded categories')"
                    />
                </h4>
                <button class="ml-2 font-bold text-secondary" @click=" toggleAdding()">
                    <IMdiMinus v-if="isAdding" title="Add new category" />
                    <IMdiPlus  v-else />
                </button>
            </div>
        </div>
        <div class="flex items-center flex-nowrap">
            <div class="w-36 text-right">
                <MoneyPresenter :value="item.budgeted" />
            </div>
            <ExpenseChartWidgetRow
                :value="item.activity"
                hide-title
                :item="item"
                type="groups"
                classes="w-44 h-full"
                :details="details"
                @open-details="fetchDetails(item)"
            />
            <div class="w-28 text-right">
                <MoneyPresenter :value="item.available" />
            </div>
            <div class="w-8 flex items-center justify-center">
                <NDropdown
                    trigger="click"
                    key-field="name"
                    :options="options"
                    :on-select="handleOptions"
                >
                    <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
                </NDropdown>
            </div>
        </div>
    </header>

    <!--
        Mobile header: single row that never scrolls sideways. The name takes
        the remaining width and truncates (no staircase wrapping); we show one
        primary number (Available - the actionable "left to spend"); an inline
        status dot replaces the desktop PointAlert (absolute-positioned, would
        be clipped by truncate); Assigned/Spent live inside the group when open.
    -->
    <header v-else class="flex items-center gap-2 px-4 py-1.5 text-body-1/80 text-xs">
        <div class="cursor-grab shrink-0" v-if="allowDrag">
            <IconDrag class="handle" />
        </div>
        <LogerButtonTab @click="isExpanded=!isExpanded" v-else class="shrink-0">
            <i class="fa" :class="toggleIcon" />
        </LogerButtonTab>
        <h4 class="flex-1 min-w-0 truncate font-bold" :class="item.hasOverspent ? 'text-error' : 'text-body'" @click="isExpanded=!isExpanded">
            {{ item.name }}
        </h4>
        <span
            v-if="item.hasOverspent"
            class="w-2 h-2 rounded-full bg-error shrink-0 animate-pulse"
            :title="$t('Has overspent categories')"
        />
        <span
            v-else-if="item.hasUnderfunded"
            class="w-2 h-2 rounded-full bg-warning shrink-0 animate-pulse"
            :title="$t('Has underfunded categories')"
        />
        <button class="font-bold text-secondary shrink-0 px-1" @click="toggleAdding()">
            <IMdiMinus v-if="isAdding" title="Add new category" />
            <IMdiPlus  v-else />
        </button>
        <div class="text-right shrink-0 tabular-nums font-semibold text-sm">
            <MoneyPresenter :value="item.available" />
        </div>
        <div class="w-6 flex items-center justify-center shrink-0">
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

    <ConfirmationModal
        :show="isConfirmingRemove"
        :title="$t('Remove category group')"
        @close="cancelRemove"
    >
        <template #content>
            <p>{{ $t('Are you sure you want to remove') }} <strong>{{ item.name }}</strong>?</p>
            <p class="mt-2 text-sm text-body-1/70">
                {{ $t('Subcategories will remain but lose their group association.') }}
            </p>
        </template>
        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <LogerButton variant="neutral" rounded @click="cancelRemove">
                    {{ $t('Cancel') }}
                </LogerButton>
                <LogerButton variant="error" rounded @click="confirmRemove">
                    {{ $t('Remove') }}
                </LogerButton>
            </div>
        </template>
    </ConfirmationModal>
</article>
</template>


