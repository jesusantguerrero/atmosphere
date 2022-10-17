<template>
<article>
    <header class="flex justify-between py-2 px-4 text-body-1/80">
        <div class="flex items-center space-x-2">
            <div class="cursor-grab">
                <IconDrag class="handle" />
            </div>
            <LogerTabButton @click="isExpanded=!isExpanded">
                <i class="fa" :class="toggleIcon" />
            </LogerTabButton>
            <div class="flex items-center">
                <h4 class="relative font-bold">
                    {{ item.name }}
                    <PointAlert
                        v-if="item.hasOverspent || item.hasOverAssigned || item.hasUnderfunded"
                    />
                </h4>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <p>
                <MoneyPresenter :value="item.activity" /> of
                <MoneyPresenter :value="item.budgeted" />
            </p>
            <LogerTabButton @click="toggleAdding">
                <i class="fa fa-plus" />
            </LogerTabButton>
            <NDropdown trigger="click" :options="options" key-field="name" :on-select="handleOptions" >
                <LogerTabButton> <i class="fa fa-ellipsis-v"></i></LogerTabButton>
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
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";

import formatMoney from "@/utils/formatMoney";
import { Inertia } from "@inertiajs/inertia";
import PointAlert from "../atoms/PointAlert.vue";
import MoneyPresenter from "./MoneyPresenter.vue";

const emit = defineEmits(['removed'])

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    forceExpanded: {
        type: Boolean,
        default: false
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
        default:
            break;
    }
}
</script>
