<script lang="ts" setup>
import { capitalize, computed } from "vue";
import DonutChart from "../organisms/DonutChart.vue";
import exactMath from "exact-math";
import { formatMoney } from "@/utils";
import { NDataTable, NPopover } from "naive-ui";
import { router } from "@inertiajs/core";
import { Link } from "@inertiajs/vue3";

const props = defineProps<{
  data: Record<string, number>[];
  componentProps: Record<string, string>;
  title?: string;
  type: string;
}>();

const emit = defineEmits(['selected']);

const handleSelection = (item: Record<string, string | number>) => {
    emit('selected', item)
};

const total = computed(() => {
  return props.data.reduce((total: number, item: Record<string, number>) => {
    try {
        return exactMath.add(total, item.total);
    } catch (err) {
        return 0
    }
  }, 0);
});

const parseDetails = (details) => {
    return !details ? null : details.split('|')
    .map((row) => {
        const rowData = row.split(':');
        return {
            id: rowData[0],
            accountName: rowData[1],
            date: rowData[2],
            payeeName: rowData[3],
            concept: rowData[4],
            amount: rowData[5],
        }
    })
}

const getCategoryLink = (item) => {
    const types = {
        categories: 'category_id',
        groups: 'group_id'
    }
    const itemField = types[props.type] ?? types.groups;
    const currentSearch = location.search.replace('?', '&');
    return `/finance/lines?${itemField}=${item.id || item.category_id}${currentSearch}`;
}
</script>

<template>
    <article class="flex items-center justify-center">
        <section class="relative w-[500px]">
            <DonutChart
              style="background: white; width: 100%"
              :series="data"
              :data="data"
              @clicked="handleSelection"
              v-bind="componentProps"
              label="name"
              value="total"
              :legend="false"
            />
            <section class="absolute text-center top-1/2 left-44  text-primary font-bold">
                <h4>
                    {{  title  }}
                </h4>
                <h5>
                    {{ formatMoney(total) }}
                </h5>
            </section>
        </section>
            <div class="space-y-1">
                <NPopover v-for="item in data" trigger="click">
                    <template #trigger>
                        <p class="w-full flex justify-between px-4 cursor-pointer">
                            <span class="font-bold">
                                {{ item.name }}:
                            </span>
                            <Link
                            class="ml-4 hover:underline group items-center hover:text-primary flex"
                            :href="getCategoryLink(item)">
                                {{  formatMoney(item.total) }}
                                <IMdiLink class="ml-1 group-hover:visible invisible" />
                            </Link>
                        </p>
                    </template>
                    <div class="">
                        <NDataTable
                            :columns="Object.keys(parseDetails(item.details)[0]).map(item => ({
                            key: item,
                            title: capitalize(item)
                            }))"
                            :data="parseDetails(item.details)"
                        />
                    </div>
                </NPopover>
            </div>
    </article>

</template>
