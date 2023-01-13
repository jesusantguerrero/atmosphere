<template>
<article>
    <header class="flex justify-between py-2 px-4 text-body-1/80">
        <div class="flex items-center space-x-2">
            <div class="cursor-grab" v-if="isMobile && allowDrag">
                <IconDrag class="handle" />
            </div>
            <LogerButtonTab @click="isExpanded=!isExpanded" v-else>
                <i class="fa" :class="toggleIcon" />
            </LogerButtonTab>
            <div class="flex items-center">
                <h4 class="relative text-primary font-bold cursor-grab" :class="{'handle': !isMobile }">
                    {{ item.name }}
                    <PointAlert
                        v-if="item.hasOverspent || item.hasOverAssigned || item.hasUnderfunded"
                    />
                </h4>
            </div>
        </div>
        <div class="flex items-center space-x-2">
                <!-- <MoneyPresenter :value="item.activity" /> of -->
                <!-- <MoneyPresenter :value="item.budgeted" /> -->
            <BudgetProgress
                :current="Math.abs(item.activity)"
                :goal="item.budgeted"
                class="h-1.5 rounded-sm w-14"
                :progress-class="['bg-success', 'bg-secondary/5']"
            >
            <template v-slot:after="{ progress }">
                {{ progress }}%
            </template>
            </BudgetProgress>
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
    <section class="pl-4 border-l-4 border-primary bg-base-lvl-3" ref="dropdown">
        <slot v-if="isExpanded || isAdding" :isExpanded="isExpanded" :toggleAdding="toggleAdding" :isAdding="isAdding" name="content"/>
    </section>
</article>
</template>

<script setup>

import { computed, ref, onMounted, watchEffect }  from "vue";
import autoAnimate from "@formkit/auto-animate"
import { NDropdown } from "naive-ui";

import IconDrag from "../icons/IconDrag.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import { Inertia } from "@inertiajs/inertia";
import PointAlert from "../atoms/PointAlert.vue";
import BudgetProgress from "./BudgetProgress.vue";

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
    name: 'add',
    label: 'Add subcategory'
},{

    name: 'delete',
    label: 'Delete'
}]

const removeCategory = () => {
    if (confirm("Are you sure you want to remove this category group?")) {
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
        case 'add':
            toggleAdding()
        default:
            break;
    }
}
</script>
