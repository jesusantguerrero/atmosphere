<template>
<LogerTabButton
    type="button"
    class="relative text-body-1/80"
>
        <IconBell class="text-xl" />
    <div v-if="notifications > 0" class="absolute bottom-0 right-0 w-4 h-4 text-xs text-white bg-error rounded-full shadow-md">
        {{ notifications }}
    </div>
</LogerTabButton>
</template>

<script setup>
import LogerTabButton from '../atoms/LogerTabButton.vue';
import IconBell from '../icons/IconBell.vue';

defineProps({
    notifications: {
        type: Number,
        default() {
            return 0
        }
    }
})

</script>
