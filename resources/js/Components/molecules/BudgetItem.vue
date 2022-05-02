<template>
<div class="flex">
    <div class="w-full">
        <button v-if="showDelete" class="text-gray-400 transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', $event)">
            <i class="fa fa-trash"></i>
        </button>
        {{ name }}
    </div>
    <div class="w-full text-right">
        {{ amount }}
    </div>
</div>
</template>

<script>
import { reactive, toRefs } from '@vue/reactivity';
import { computed } from '@vue/runtime-core';
import { useMoney } from "@/utils/useMoney";

export default {
    props: {
        item: {
            type: Object,
            required: true
        },
        showDelete: {
            type: Boolean,
            required: false,
        }
    },
    setup(props) {
        const { formatMoney } = useMoney()
        const state = reactive({
            amount: computed(() => {
               return 0 // formatMoney(props.item.amount)
            }),
            name: props.item.name
        });

        return {
            ...toRefs(state)
        }
    },
}
</script>

<style>

</style>
