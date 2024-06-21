<script setup lang="ts">
import { computed } from 'vue';
import { AtButton } from "atmosphere-ui";

const variants = {
  primary: "bg-primary text-white",
  secondary: "bg-secondary border-secondary text-white",
  success: "bg-success text-white",
  error: "bg-error/80 text-white",
  neutral: "bg-base-lvl-2 text-body-1 border-transparent",
  inverse: "border-primary bg-primary/10 text-primary hover:bg-primary hover:text-white",
  "inverse-secondary":
    "border-secondary bg-secondary/10 text-secondary hover:bg-secondary hover:text-white",
}

export type ButtonVariants = keyof typeof variants;

const props = withDefaults(defineProps<{
    variant?: ButtonVariants,
    as?: Object| string,
    processing?: boolean,
    disabled?: boolean
    icon?: string | Object
}>(), {
    as: AtButton
});

const typeClasses = computed(() => {
    return variants[props.variant as ButtonVariants] || variants.primary
})
</script>

<template>
 <component
    :is="as" class="relative flex items-center px-5 py-2 font-bold transition border rounded-md min-w-max"
    :class="[typeClasses]"
    :disabled="processing || disabled"
>
    <section class="flex items-center">
        <div  :class="{'mr-2': $slots.default}">
            <slot name="icon" v-if="!processing" >
                <component :is="icon" v-if="icon" />
            </slot>
            <IMdiSync  v-else class="animate-spin"/>
        </div>
        <slot />
    </section>
 </component>
</template>


