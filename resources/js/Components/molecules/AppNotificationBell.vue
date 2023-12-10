<script setup lang="ts">
import LogerButtonCircle from '@/Components/atoms/LogerButtonCircle.vue';
import IconBell from '@/Components/icons/IconBell.vue';

defineProps({
    notifications: {
        type: Number,
        default() {
            return 0
        }
    }
})

</script>

<template>
<LogerButtonCircle
    type="button"
>
    <IconBell class="text-md" />
    <div v-if="notifications > 0" class="absolute bottom-0 right-0 w-4 h-4 text-xs text-white rounded-full shadow-md bg-error">
        {{ notifications }}
    </div>
</LogerButtonCircle>
</template>
