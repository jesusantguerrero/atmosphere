<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from "@inertiajs/vue3";
import { nameToColor } from "@/utils";

import DonutChart from '@/Components/organisms/DonutChart.vue';
import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';

const { t } = useI18n();

const props = defineProps({
    groupData: {
        type: Array,
        default() {
            return []
        }
    },
    categoryData: {
        type: Array,
        default() {
            return []
        }
    }
})

const isGroup = ref(true);
const toggleType = () => {
    isGroup.value = !isGroup.value
}

const typeData = computed(() => {
    return isGroup.value ? props.groupData : props.categoryData;
})


const typeLabel = computed(() => {
    return isGroup.value ? t('Category Group') : t('Categories');
})
</script>

<template>
<WidgetTitleCard
    :title="typeLabel"
    :action="{
        label: $t('Go to Trends'),
        iconClass: 'fa fa-chevron-right',
    }"
    @action="router.visit('/trends')"
>
    <template #afterActions>
        <button @click="toggleType" :title="isGroup ? $t('Switch to categories') : $t('Switch to groups')" class="transform animate" :class="[!isGroup && '-rotate-180']">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--ic" width="32" height="32" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M12 7C6.48 7 2 9.24 2 12c0 2.24 2.94 4.13 7 4.77V20l4-4l-4-4v2.73c-3.15-.56-5-1.9-5-2.73c0-1.06 3.04-3 8-3s8 1.94 8 3c0 .73-1.46 1.89-4 2.53v2.05c3.53-.77 6-2.53 6-4.58c0-2.76-4.48-5-10-5z"></path></svg>
        </button>
    </template>
    <article class="w-full">
        <DonutChart
            class="mx-auto flex justify-center"
            style="height:270px; background: rgb(var(--c-base-lvl-3)); width: 100%"
            :series="typeData"
            label="name"
            value="total"
        />
    </article>
    <ul class="flex flex-wrap justify-center px-2 mt-2 gap-x-4 gap-y-1">
        <li v-for="item in typeData" :key="item.name" class="flex items-center gap-1.5 text-xs text-body-1/70">
            <span class="inline-block rounded-sm w-2.5 h-2.5" :style="{ background: item.color || nameToColor(item.name) }" />
            <span class="capitalize">{{ item.name }}</span>
        </li>
    </ul>
</WidgetTitleCard>

</template>


