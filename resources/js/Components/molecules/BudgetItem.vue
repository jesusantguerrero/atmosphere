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
            <p>
                {{ formatMoney(item.budgeted) }} of {{ formatMoney(item.spent || item.goal) }}
            </p>
        </div>
    </div>
    <div class="flex items-center space-x-2 text-right flex-nowrap min-w-fit">
        <BalanceInput :value="item.available" :formatter="formatMoney" />
        <LogerTabButton> <i class="fa fa-ellipsis-v"></i></LogerTabButton>
    </div>
</div>
</template>

<script setup>
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
