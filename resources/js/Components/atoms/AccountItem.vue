<template>
    <div
        class="flex justify-between px-2 py-2 overflow-hidden rounded-md cursor-pointer hover:bg-base-lvl-3 group"
        :class="{'bg-base-lvl-2': isSelected}"
        v-bind="$attrs"
        v-on="$attrs"
        :title="account.balance"
    >
        <div>
            <strong class="flex flex-wrap overflow-hidden overflow-ellipsis">
                {{ account.name }}
            </strong>
            <p class="relative text-sm" :class="{'text-red-400': isDebt(account.balance)}">
                <NumberHider />
                {{ formatMoney(account.balance, account.currency_code) }}
            </p>
        </div>
        <div class="w-4">
            <IconDrag class="hidden h-10 group-hover:inline-block handle"/>
        </div>
    </div>
</template>

<script setup>
import NumberHider from '@/Components/molecules/NumberHider.vue';
import formatMoney from '@/utils/formatMoney';
import IconDrag from '../icons/IconDrag.vue';

defineProps({
   account: {
       type: Object,
       required: true
   },
   isSelected: {
        type: Boolean,
        default: false
   }
})

const isDebt = (amount) => {
    return amount < 0
}
</script>
