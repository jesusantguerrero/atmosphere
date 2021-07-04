<template>
    <app-layout>
        <div class="flex mx-auto mt-5 space-x-4 max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="w-9/12">
                <welcome />
            </div>
            <div class="w-3/12">
                <div class="space-y-5">
                    <div class="px-4 py-3 bg-white rounded-lg shadow-md">
                        <div class="flex justify-between">
                            <h4 class="text-2xl font-bold text-blue-700"> Get random meal</h4>
                            <at-button class="text-white bg-pink-500 rounded-full"> <i class="fa fa-sync"></i></at-button>
                        </div>
                        <div class="h-20">
                        </div>
                    </div>

                    <h4 class="text-2xl font-bold text-pink-500"> Menu for today</h4>
                    <div class="px-4 py-3 bg-white rounded-lg shadow-md cursor-pointer" v-for="plan in meals" :key="plan.id">
                        <h4 class="font-bold text-blue-700">
                            {{ plan.dateable.name }}
                        </h4>
                        <small class="text-gray-400">Lunch</small>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout'
    import Welcome from '@/Components/Welcome'
    import { AtButton } from "atmosphere-ui/dist/atmosphere-ui.es.js";

    export default {
        components: {
            AppLayout,
            Welcome,
            AtButton
        },
        props: {
            meals: {
                type: Array,
                required: true
            }
        }
    }
</script>
