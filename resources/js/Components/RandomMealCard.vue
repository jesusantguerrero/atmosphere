<template>
    <div class="flex items-center justify-between px-4 py-3 rounded-lg shadow-md bg-base-lvl-3">
        <div class="flex items-center h-10 text-lg text-center capitalize text-secondary">
            <span>
                {{ state.label }}
            </span>
        </div>
        <div class="flex justify-between">
            <LogerButton variant="inverse" class="rounded-full" @click="getRandomMeal" :disabled="state.isLoading">
                <i class="fa fa-sync" :class="[state.isLoading ? 'fa-spin' : '']"></i>
            </LogerButton>
        </div>

    </div>
</template>

<script setup>
import { AtButton } from "atmosphere-ui"
import { reactive, computed } from 'vue'
import axios from 'axios'
import LogerButton from "./atoms/LogerButton.vue";
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
            type: String
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
       return state.mealName ?? 'Get random meal'
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
