<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import { formatMoney } from '@/utils';
import axios from 'axios';


const getWatchlist = () => {
    return axios.get('/api/finance/watchlist').then(({data}) => data.data);
}

const items = ref()
onMounted(async () => {
    items.value = await getWatchlist()
})
</script>

<template>
<main class="space-y-2 pt-4">
    <LogerButtonTab class="flex items-center justify-between w-full font-bold" v-for="item in items">
        <span class="flex items-center">
            <IMdiStar class="mr-2 text-md text-secondary" />
            {{  item.name  }}

        </span>
        <span class="text-secondary">
            {{  formatMoney(item?.data?.month.total) }}
        </span>
    </LogerButtonTab>
</main>
</template>
