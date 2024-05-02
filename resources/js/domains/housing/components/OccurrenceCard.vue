<script setup lang="ts">
import { computed, reactive } from "vue"

import SiteUptime  from "./SiteUptime.vue";
import { IOccurrenceCheck } from "../models";
import { formatDate, getDayDiff } from "@/utils";

const props = defineProps<{
    occurrence: IOccurrenceCheck;
    showControls: boolean;
    disabled: boolean;
    isLoading: boolean;
}>();

defineEmits(['delete', 'add-instance', 'remove-instance'])

const state = reactive({
    uptime: computed(() => {
        return props.occurrence.responses ? props.occurrence.responses.reduce((dailyResponse, response) => {
            dailyResponse.calls = sum(dailyResponse.calls,  response.calls);
            dailyResponse.success = sum(dailyResponse.success, response.success);
            return dailyResponse;
        }, {calls: 0, success: 0 }) : { calls: 0, success: 0 };
    }),
    uptimePercent: computed(() => {
        const percent = (state.uptime.success / state.uptime.calls * 100) || 0;
        return percent.toFixed(2);
    }),
    siteStatusClass: computed(() => {
        const colors = {
            OPERATIONAL: 'text-green-500',
            DEGRADED: 'text-yellow-500',
            PARTIAL: 'text-orange-500',
            MAJOR: 'text-red-500',
            MAINTENANCE: 'text-blue-500'
        };
        return colors[props.occurrence.status] || 'text-gray-400';
    }),
    statusColors: computed(() => {
        const colors = {
            500: 'bg-secondary/10 text-secondary',
            200: 'bg-green-100 text-green-500',
        }
        return colors[props.occurrence.status] || colors[500];
    })
})

const sum = (number1, number2) => {
    return Number(number1) + Number(number2)
}

const asideClass = computed(() => ({
    'opacity-50': props.disabled,
    'grid grid-rows-2': !props.isLoading,
    'items-center justify-center flex w-12': props.isLoading
})) 
</script>

<template>
    <article class="flex flex-col pl-5  md:space-x-10 md:occurrences-center md:flex-row md:flex" @submit.prevent="submit">
      <section class="flex occurrences-center py-3 justify-between md:w-3/12">
          <div class="flex">
              <h4 class="text-primary">{{ occurrence.name }}</h4>
            </div>
            
            <div class="text-center">
                <span class="w-28 block text-center px-2 py-1 ml-5 font-bold rounded-full" :class="state.statusColors" v-if="occurrence.last_date" title="Days passed from last instance">
                    {{ getDayDiff(occurrence.last_date) }}
                </span>
            <span class="ml-1 text-xs text-body-1" title="last occurrence on"> {{ formatDate(occurrence.last_date) }}</span>
          </div>
      </section>
      <section class="w-full md:w-6/12 py-3">
          <div class="flex occurrences-center text-sm text-gray-400 uppercase items-center">
              <span class="hidden w-16 md:inline-block">{{ occurrence.log.length }}</span>
              <SiteUptime :responses="occurrence.log" container-class="gap-0.5 md:ml-5 opacity-60"/>
          </div>
      </section>
      <section class="flex items-center justify-center occurrences-center w-full mt-2 md:w-3/12 md:justify-end md:mt-0 md:space-x-2">
          <p>
              <span class="font-bold" :class="[state.siteStatusClass]"> AVG: </span>
              <span class="font-bold text-gray-500">{{ occurrence.avg_days_passed }} Days</span>
          </p>
          <p>
              <span class="font-bold" :class="[state.siteStatusClass]"> Prev: </span>
              <span class=""> {{ occurrence.previous_days_count }} days</span>
          </p>
          <section>
              <slot name="actions" :scope="occurrence" />
          </section>
          <aside  
            class="h-full " 
            :class="asideClass">
            <IMdiSync  class="animate-spin" v-if="isLoading" />
            <template v-else>
                <button 
                    class=" px-3 py-3 rounded-tl-md dark:bg-base-lvl-1 bg-gray-200 transition-colors" 
                    :class="{'hover:bg-green-400 dark:hover:bg-green-400 hover:text-white': !disabled }"
                    @click.stop="!disabled && $emit('add-instance', occurrence)">
                        <IMdiChevronUp />
                </button>
                <button 
                    class="px-3 py-3 rounded-bl-md dark:bg-base-lvl-1 bg-gray-200 transition-colors" 
                    :class="{'hover:bg-red-400 dark:hover:bg-red-400 hover:text-white': !disabled}"
                    @click.stop="!disabled && $emit('remove-instance', occurrence)">
                        <IMdiChevronDown />
                </button>
            </template>
        </aside>
      </section>
    </article>
  </template>
