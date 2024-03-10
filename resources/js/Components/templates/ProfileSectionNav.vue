
<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { computed } from "vue";


const props = defineProps<{
    logerProfile: any[]
}>();



const sections = computed(() => {
    return [
        ...(props.logerProfile?.map((profile: Record<string, string>) => ({
            icon: profile.icon,
            name: profile.name,
            label: profile.name,
            url: `/loger-profiles/${profile.id}`
        })) ?? [])
    ]
})
</script>


<template>
<SectionNav :sections="sections" @action="$emit('action', $event)">
    <template #actions>
        <slot name="actions" />
    </template>
</SectionNav>
</template>
