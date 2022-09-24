<template>
<SectionNav :sections="[{
    label: 'Overview',
    url: '/finance'
}, {
    label: 'Transactions',
    url: '/finance/transactions'
},
{
    label: 'Budget',
    url: '/budgets'
},
{
    label: 'Watchlist',
    url: '/finance/watchlist'
},
 {
    label: 'Trends',
    url: '/trends'
}]">
    <template #actions>
        <slot name="actions" />
    </template>
</SectionNav>
</template>


<script setup>
import SectionNav from "@/Components/molecules/SectionNav.vue";

</script>
