<template>
    <div class="px-4 py-3 rounded-lg shadow-md bg-base-lvl-3">
        <div class="flex justify-between">
            <h4 class="font-bold text-body"> Random meal</h4>
            <LogerButton variant="inverse" class="rounded-full" @click="getRandomMeal" :disabled="state.isLoading"> 
                <i class="fa fa-sync" :class="[state.isLoading ? 'fa-spin' : '']"></i>
            </LogerButton>
        </div>
        <div class="h-10 text-lg text-center text-secondary">
            {{ state.mealName  }}
            <div v-if="state.isLoading"> <i class="fa fa-spin fa-spinner"></i></div>
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
