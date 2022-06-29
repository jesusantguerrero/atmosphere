<template>
    <div class="rounded-md border-l border-slate-600 px-5 text-slate-200">
        <AtField label="Import Transactions">
            <input type="file" />
            <button class="text-slate-200" @click="handleImport">Import</button>
        </AtField>
        <div class="space-y-2">
            <div v-for="account in accounts" :key="account.id" class="hover:bg-slate-600 cursor-pointer py-2 px-2 rounded-md">
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
import { AtField } from "atmosphere-ui"
import { Inertia } from '@inertiajs/inertia';
import NumberHider from '@/Components/molecules/NumberHider.vue';
import formatMoney from '@/utils/formatMoney';


const handleImport = async () => {
    const file = document.querySelector('input[type="file"]').files[0]
    Inertia.post('/financial/import', {
        file
    })
}

defineProps({
   accounts: {
       type: Array,
       default: () => []
   },
})
</script>
