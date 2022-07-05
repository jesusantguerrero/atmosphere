<template>
    <div class="rounded-md border-l border-slate-600 px-5 text-slate-200">
        <div class="space-y-2">
            <div v-for="account in accounts" :key="account.id"
                class="hover:bg-slate-600 cursor-pointer py-2 px-2 rounded-md"
                :class="{'bg-slate-500': isSelectedAccount(account.id)}"
                @click="Inertia.visit(`/finance/${account.id}/transactions`)"
            >
                    <strong>
                        {{ account.name }}
                    </strong>
                    <p class="relative text-sm">
                        <NumberHider />
                        {{ formatMoney(account.balance) }}
                    </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import NumberHider from '@/Components/molecules/NumberHider.vue';
import formatMoney from '@/utils/formatMoney';
import { Inertia } from "@inertiajs/inertia"
import { usePage } from '@inertiajs/inertia-vue3';

const pageProps = usePage().props

const isSelectedAccount = (accountId) => {
    return pageProps.value.accountId == accountId
}

defineProps({
   accounts: {
       type: Array,
       default: () => []
   },
})
</script>
