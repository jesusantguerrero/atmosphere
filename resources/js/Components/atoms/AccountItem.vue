<template>
    <div
        class="hover:bg-slate-600 cursor-pointer py-2 px-2 rounded-md overflow-hidden"
        :class="{'bg-slate-500': isSelected}"
        v-bind="$attrs"
        v-on="$attrs"
        :title="account.balance"
    >
            <strong class="flex flex-wrap overflow-ellipsis overflow-hidden">
                {{ account.name }}
            </strong>
            <p class="relative text-sm">
                <NumberHider />
                {{ formatMoney(account.balance) }}
            </p>
    </div>
</template>

<script setup>
import NumberHider from '@/Components/molecules/NumberHider.vue';
import formatMoney from '@/utils/formatMoney';

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
</script>
