<script lang="ts" setup>
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import RandomMealCard from '@/Components/widgets/RandomMealCard.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import ChoreWidgetCard from '@/Components/widgets/ChoreWidgetCard.vue';
import { IBoard } from "@/domains/housing/models";

defineProps<{
    boards: IBoard[]
}>()
</script>

<template>
    <div class="pb-10"  :class="cardShadow">
        <header class="flex items-center justify-between px-4 py-3 rounded-lg bg-base-lvl-3">
            <div class="flex text-lg items-center text-center capitalize text-secondary">
                <h4>
                    Boards
                </h4>
            </div>
            <div class="flex justify-between">
                <LogerButton
                    variant="inverse"
                    class="rounded-full text-xs h-10"
                >
                    <template #icon>
                        <IMdiSync  />
                    </template>
                </LogerButton>
                <slot name="actions" />
            </div>
        </header>
        <section class="px-4 bg-base-lvl-3 pb-8">
            <section class="px-4 py-2 space-y-4 rounded-b-md cursor-pointer min-h-min ">
                <slot v-if="boards.length">
                    <section class="flex space-x-4 py-4">
                        <ChoreWidgetCard :color="board.color" :board="board" v-for="board in boards" />
                    </section>
                </slot>
            </section>
            <section>
                <h4> Next Updates</h4>
            </section>
        </section>
    </div>
</template>
