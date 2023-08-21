<script setup lang="ts">
   defineProps<{
    photoUrl: string;
    userName: string;
    email: string;
   }>()
</script>


<template>
    <div class="flex items-center mt-2">
        <img class="object-cover w-12 h-12 rounded-full" :src="photoUrl" :alt="userName">

        <div class="ml-4 leading-tight">
            <div>{{ userName }}</div>
            <div class="text-sm text-gray-700">{{ email }}</div>
        </div>
    </div>
</template>
