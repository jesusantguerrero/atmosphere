
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
    },
    hideDivider: {
        type: Boolean
    },
    withPadding: {
        type: Boolean,
        default: true,
    }
})
</script>


<template>
    <div class="py-3 transition text-body border rounded-lg  border-base bg-base-lvl-3"
    :class="[cardShadow, hideDivider ? '' : 'divide-base divide-y ']">
        <div class="px-5 flex items-center justify-between pb-2">
            <h1 class="font-bold flex items-center">
                <section class="bg-white text-primary w-8 h-8 rounded-full flex items-center justify-center mr-2"
                    v-if="$slots.icon"
                >
                    <slot name="icon"></slot>
                </section>
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
        <div class="flex py-3" :class="{'px-5': withPadding}">
            <slot></slot>
        </div>
    </div>
</template>
