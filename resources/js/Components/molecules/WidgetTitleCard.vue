
<script setup lang="ts">
// @ts-expect-error: no definitions
import { AtButton } from "atmosphere-ui";

defineProps({
    title: {
        type: String,
        required: true
    },
    action: {
        type: Object,
    }
})
</script>


<template>
    <div class="px-5 py-3 transition border divide-y rounded-lg divide-base border-base bg-base-lvl-3" :class="cardShadow">
        <div class="flex items-center justify-between pb-2">
            <h1 class="font-bold text-body">
                <slot name="title"> {{ title }}</slot>
            </h1>
            <div class="flex items-center space-x-2">
                <slot name="beforeActions" />

                <slot name="action" v-if="action || $slots.action">
                    <AtButton v-if="action" class="text-sm text-primary" rounded @click="$emit('action')">
                        <span>
                            {{ action.label }}
                        </span>
                        <i class="ml-2" :class="action.iconClass" v-if="action.iconClass" />
                    </AtButton>
                </slot>
                <slot name="afterActions" />
            </div>
        </div>
        <div class="flex py-3">
            <slot></slot>
        </div>
    </div>
</template>