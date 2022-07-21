<template>
<div class="flex space-between py-2 px-4">
    <div class="w-full flex items-center space-x-4">
        <button v-if="showDelete" class="text-gray-400 transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', $event)">
            <i class="fa fa-trash"></i>
        </button>
        <div class="cursor-grab mr-4">
            <IconDrag class="handle" />
        </div>
        <div>
            <h4> {{ item.name }} </h4>
            <p>
                {{ formatMoney(item.budgeted) }} of {{ item.goal }}
            </p>
        </div>
    </div>
    <div class="flex text-right space-x-2 items-center flex-nowrap min-w-fit">
        <span class="min-w-fit"> {{ formatMoney(item.spent) }}</span>
        <BalanceInput :value="item.available" :formatter="formatMoney" />
        <LogerTabButton @click="toggleAdding">
            <i class="fa fa-plus" />
        </LogerTabButton>
        <LogerTabButton> <i class="fa fa-ellipsis-v"></i></LogerTabButton>
    </div>
</div>
</template>

<script setup>
import { reactive, toRefs } from '@vue/reactivity';
import { computed } from '@vue/runtime-core';
import IconDrag from '../icons/IconDrag.vue';
import formatMoney from "@/utils/formatMoney";
import BalanceInput from "@/Components/atoms/BalanceInput.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";

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

</script>

<style>

</style>
