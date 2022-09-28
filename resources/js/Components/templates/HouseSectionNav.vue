<template>
<SectionNav :sections="[{
    label: 'Overview',
    url: '/projects'
},
{
    label: 'Chores',
    url: '/house/chores'
},
{
    label: 'Occurrence Checks',
    url: '/house/checks'
},
{
    label: 'Tasks',
    url: '/house/tasks'
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
