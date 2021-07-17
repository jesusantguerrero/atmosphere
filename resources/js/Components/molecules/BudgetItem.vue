<template>
<div class="flex">
    <div class="w-full">
        {{ name }}
    </div>
    <div class="w-full">
        {{ amount }}
    </div>
</div>
</template>

<script>
import { dinero, toFormat } from 'dinero.js';
import { USD, DOP } from '@dinero.js/currencies';
import { reactive, toRefs } from '@vue/reactivity';
import { computed } from '@vue/runtime-core';

export default {
    props: {
        item: {
            type: Object,
            required: true
        }
    },
    setup(props) {
        const formatter = ({amount, currency }) => `${currency.code} ${amount}`
        const state = reactive({
            amount: computed(() => {
                const value = dinero({ amount: Number(props.item.amount.replace('.', '')), currency: DOP })
                return toFormat(value, formatter);
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
