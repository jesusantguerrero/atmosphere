<template>
 <AtButton class="mt-4 border  font-bold rounded-md" :class="[typeClasses]">
    <slot />
 </AtButton>
</template>


<script setup>
import { computed } from 'vue';
import { AtButton } from "atmosphere-ui";

const types = {
    primary: "bg-primary text-white",
    inverse: "border-primary bg-primary/10 text-primary"
}

const props = defineProps({
    type: {
        type: String,
        default: "primary"
    }
})

const typeClasses = computed(() => {
    return types[props.type] || types['primary']
})
</script>

