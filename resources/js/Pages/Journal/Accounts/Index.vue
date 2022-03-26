<template>
    <app-layout>
        <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">
            <section-title type="secondary"> Accounts</section-title>

            <div class="flex flex-wrap mt-4 space-x-2 cursor-pointer">
                <div v-for="account in accounts" :key="account.id" class="px-5 py-3 rounded-md bg-white border-2 mb-4">
                    <p>{{ account.name }}</p>
                    <strong class="mt-2 block">{{ account.balance }}</strong>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout'
    import SectionTitle from "@/Components/atoms/SectionTitle";

    defineProps({
        categories: {
            type: Array,
            default: () => []
        },
        accounts: {
            type: Array,
            default: () => []
        },
    })
</script>
