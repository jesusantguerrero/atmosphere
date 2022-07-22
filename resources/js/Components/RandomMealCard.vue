<template>
    <div class="px-4 py-3 rounded-lg shadow-md bg-base-lvl-1">
        <div class="flex justify-between">
            <h4 class="text-2xl font-bold text-gray-200"> Get random meal</h4>
            <at-button class="text-white bg-primary-500 rounded-full" @click="getRandomMeal" :disabled="state.isLoading"> <i class="fa fa-sync"></i></at-button>
        </div>
        <div class="h-20 text-lg text-center text-gray-100">
            {{ state.mealName  }}
            <div v-if="state.isLoading"> <i class="fa fa-spin fa-spinner"></i></div>
        </div>
    </div>
</template>

<script setup>
import { AtButton } from "atmosphere-ui"
import { reactive, computed } from 'vue'
import axios from 'axios'
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
