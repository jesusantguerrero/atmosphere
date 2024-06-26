<script setup lang="ts">
import { reactive, computed } from 'vue'
import axios from 'axios'
import LogerButton from "@/Components/atoms/LogerButton.vue";

const props = defineProps({
        show: {
            default: false
        },
        maxWidth: {
            default: '2xl'
        },
        closeable: {
            default: true
        },
        meals: {
            type: Array,
            default() {
                return []
            }
        },
        date: {
            default: new Date()
        },
        title: {
            type: String,
            default: 'Get random meal'
        }
});

defineEmits(['close']);

const state = reactive({
    meal: null,
    isLoading: false,
    mealName: computed(() => {
        return state.meal && state.meal.name
    }),
    label: computed(() => {
        if (state.isLoading) return "Loading..."
       return state.mealName ?? props.title
    })
});

const getRandomMeal = () => {
    if (state.isLoading) return;
    state.meal = null;
    state.isLoading = true;
    axios.get('/meals-random').then(({ data }) => {
        state.meal = data;
    }).finally(() => {
        state.isLoading = false;
    })
}
</script>

<template>
    <div class="flex items-center justify-between px-4 py-3 rounded-lg bg-base-lvl-3">
        <div class="flex items-center text-center capitalize text-secondary">
            <span>
                {{ state.label }}
            </span>
        </div>
        <div class="flex justify-between">
            <LogerButton
                variant="inverse"
                class="rounded-full text-xs h-6"
                @click="getRandomMeal"
                :processing="state.isLoading"
            >
                <template #icon>
                    <IMdiSync  />
                </template>
            </LogerButton>
            <slot name="actions" />
        </div>
    </div>
</template>
