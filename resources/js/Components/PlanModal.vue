<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-10 pt-5 pb-4">
            <h3 class="text-lg font-bold text-pink-500">
                <slot name="title">
                    {{ title }}
                </slot>
            </h3>
        </div>

        <div class="px-4 pb-4 bg-slate-600 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <div class="w-full text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <slot name="content">
                        <div class="w-full">
                            <at-input v-model="searchText" placeholder="Search meal by name" type="search" class="w-full" />
                            <meal
                                class="overflow-auto h-96"
                                :meals="filteredMeals"
                                :selected="selectedMeals"
                                @click="handleSelect"
                            />
                        </div>
                    </slot>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-gray-100">
            <at-button type="secondary" @click="close"> Cancel </at-button>
            <at-button
                class="text-white bg-pink-400"
                @click="submit"
                :disabled="!hasValidMeals"
            > Save
            </at-button>
        </div>
    </modal>
</template>

<script setup>
    import Modal from '@/Jetstream/Modal'
    import { useForm } from "@inertiajs/inertia-vue3"
    import { AtInput, AtButton } from "atmosphere-ui"
    import { startOfDay } from 'date-fns'
    import {  watch, ref, computed } from 'vue'
    import Meal from './Meal.vue'

    const emit = defineEmits(['close'])
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
            selected: {
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

    const form = useForm({
        name: ''
    });
    const selectedMeals = ref(props.selected);
    const searchText = ref("");

    const filteredMeals = computed(() => {
        return props.meals.filter( meal => meal.name.toLowerCase().includes(searchText.value.toLowerCase()))
    })

    watch(() => props.selected, (selected) => {
        selectedMeals.value = selected;
    }, { immediate: true })

    const close = () =>  {
        emit('close')
    }

    const handleSelect = (meal) => {
        const isSelected = selectedMeals.value.findIndex(selected => selected.id == meal.id)
        if (isSelected >= 0) {
            selectedMeals.value.splice(isSelected, 1);
        } else {
            selectedMeals.value.push(meal)
        }
    }

    const getValidMeals = (meals) => {
        return meals.
            filter(meal => meal.id && !meal.schedule_id )
            .map(meal => ({
                id: meal.id
            }))
    }

    const hasValidMeals = computed(() => getValidMeals(selectedMeals.value).length);

    const submit = () => {
        if (!hasValidMeals.value) return
        form
        .transform(()=> ({
            date: startOfDay(props.date),
            meals: getValidMeals(selectedMeals.value)
        }))
        .post(route('meals.addPlan'), {
            onSuccess: () => {
                selectedMeals.value = [];
                emit('close')
            }
        })
    }
</script>
