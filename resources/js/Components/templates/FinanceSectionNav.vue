<template>
<SectionNav :sections="[{
    label: 'Overview',
    url: '/finance'
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
    label: 'Transactions',
    url: '/finance/transactions'
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
