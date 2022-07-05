<template>
    <div class="rounded-md border-l border-slate-600 px-5 text-slate-200">
        <div class="space-y-2">
            <AccountItem v-for="account in accounts"
                :key="account.id"
                :account="account"
                :is-selected="isSelectedAccount(account.id)"
                @click="Inertia.visit(`/finance/${account.id}/transactions`)"
            />
        </div>
    </div>
</template>

<script setup>
import { Inertia } from "@inertiajs/inertia"
import { usePage } from '@inertiajs/inertia-vue3';
import AccountItem from '@/Components/atoms/AccountItem.vue';

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
