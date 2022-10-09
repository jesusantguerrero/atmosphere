<template>
    <div class="px-5 py-3 transition border divide-y rounded-lg divide-base border-base bg-base-lvl-3" :class="cardShadow">
        <div class="items-center pb-2 md:justify-between md:flex">
            <h1 class="font-bold text-body">
                <slot name="title"> {{ title }}</slot>
            </h1>
            <div class="space-x-2">
                <AtButton class="text-sm text-primary" rounded @click="$inertia.visit(route('budgets.index'))">
                    <i class="fa fa-wallet"></i>
                    Edit budget
                </AtButton>
            </div>
        </div>
        <div class="flex py-3">
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
import { AtButton } from "atmosphere-ui";
import { computed, ref } from "vue";

import SectionTitle from "@/Components/atoms/SectionTitle.vue";;

const props = defineProps({
    title: {
        type: String,
        required: true
    },
})
</script>
