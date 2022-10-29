<template>
<SectionNav :sections="[{
    label: 'Overview',
    url: '/housing'
},
{
    label: 'Chores',
    url: '/housing/chores'
},
{
    label: 'Occurrence Checks',
    url: '/housing/occurrence'
},
{
    label: 'Tasks',
    url: '/housing/tasks'
},
 {
    label: 'Equipment',
    url: '/equipment'
}]">
    <template #actions>
        <slot name="actions" />
    </template>
</SectionNav>
</template>


<script setup>
import SectionNav from "@/Components/molecules/SectionNav.vue";

</script>
