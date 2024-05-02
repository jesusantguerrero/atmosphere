<script setup lang="ts">
      import { formatDate } from "@/utils";
import { NPopover } from "naive-ui"
      import { computed } from "vue-demi";
      
      const props = withDefaults(defineProps<{
        max: number;
        responses: string[];
        containerClass: string;
      }>(), {
          max:24,
          responses: () => ([])
      })
  
   
      const progressClass = (response) => {
          return response.status >= 400 ? 'bg-primary' : 'bg-secondary';
      };
  
      const lastResponses = computed(() => {
          return props.responses.reverse().slice(0, props.max).reverse();
      });
  </script>

<template>
    <article class="flex w-full items-center">
        <section class="grid w-full mt-2 bg-gray-50 h-9 md:grid-cols-16 lg:grid-cols-30 place-items-end" :class="containerClass">
          <div v-for="(response, index) in lastResponses" :key="`${response}-${index}`" class="w-full h-full cursor-pointer active">
              <NPopover placement="bottom" trigger="hover">
                  <template #trigger>
                      <div  :class="progressClass(response)" class="w-full h-full bg-secondary bg-opacity-75 rounded-md cursor-pointer hover:bg-gray-500 bg-op" />
                  </template>
                  <p class="font-bold text-gray-500">{{ formatDate(response)}}</p>
                  Event Happened
              </NPopover>
          </div>
        </section>
    </article>
  </template>