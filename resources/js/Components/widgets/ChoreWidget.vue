<script lang="ts" setup>
import ChoreWidgetCard from '@/Components/widgets/ChoreWidgetCard.vue';
import { IBoard } from "@/domains/housing/models";

defineProps<{
    boards: IBoard[]
}>()
</script>

<template>
    <div class="flex flex-wrap gap-4">
        <ChoreWidgetCard
            v-for="board in boards"
            :key="board.id"
            :color="board.color"
            :board="board"
        />
    </div>
</template>
