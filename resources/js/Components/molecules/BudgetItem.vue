<template>
<div class="flex">
    <div class="w-full">
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
        }
    },
    setup(props) {
        const { formatMoney } = useMoney()
        const state = reactive({
            amount: computed(() => {
               return formatMoney(props.item.amount)
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
