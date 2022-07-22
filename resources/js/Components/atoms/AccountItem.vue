<template>
    <div
        class="hover:bg-base-lvl-1 cursor-pointer py-2 px-2 rounded-md overflow-hidden flex justify-between group"
        :class="{'bg-base-lvl-2': isSelected}"
        v-bind="$attrs"
        v-on="$attrs"
        :title="account.balance"
    >
        <div>
            <strong class="flex flex-wrap overflow-ellipsis overflow-hidden">
                {{ account.name }}
            </strong>
            <p class="relative text-sm" :class="{'text-red-400': isDebt(account.balance)}">
                <NumberHider />
                {{ formatMoney(account.balance) }}
            </p>
        </div>
        <div class="w-4">
            <IconDrag class="hidden group-hover:inline-block h-10 handle"/>
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
