<script setup lang="ts">
    import { ref, onMounted } from 'vue';


    import ChartComparison from "@/Components/widgets/ChartComparison.vue";
    import ChartCurrentVsPrevious from "@/Components/widgets/ChartCurrentVsPrevious.vue";

    import WidgetContainer from '@/Components/WidgetContainer.vue';

    import { router } from '@inertiajs/vue3';

    withDefaults(defineProps<{
        spendingSummary: {
            previousYear: {
                values: []
            },
            currentYear: {
                values: []
            }
        },
        expenses: {
          previousYear: {
            values: []
          },
          currentYear: {
             values: []
          }
        },
    }>(), {

    });



    const areChecksLoading = ref(true);
    const fetchChecks = () => {
        return router.reload({
            only: ['checks'],
            onFinish: () => {
                areChecksLoading.value = false
            }
        });
    }

    onMounted(() => {
        fetchChecks()
    })


    const itemLabel = (row: any) => {
        return `${row.name}, ${row.target_type}`;
    }

    const financeTabs = [{
      name: "monthVsPrevious",
      label: "Previous",
    },{
      name: "spendingSummary",
      label: "Spending",
    }];


    const goToCategoryMonth = (category: IBudgetCategory, month: any) => {
        debugger
        router.visit(`/finance/lines?filter[category_id]=${category.id}`)
    }
</script>

<template>
<WidgetContainer
    :message="$t('Financial glance')"
    :tabs="financeTabs"
    default-tab="monthVsPrevious"
    class="order-2 mt-4 lg:mt-0 lg:order-1"
>
    <template v-slot:content="{ selectedTab }">
        <ChartCurrentVsPrevious
            v-if="selectedTab=='monthVsPrevious'"
            class="w-full  md:mb-10 overflow-hidden bg-white rounded-lg"
            :class="[cardShadow]"
            :title="$t('This month vs last month')"
            ref="ComparisonRevenue"
            :data="expenses"
        />

        <ChartComparison
            v-else
            class="w-full md:mb-10 overflow-hidden bg-white rounded-lg"
            :class="[cardShadow]"
            :title="$t('Spending summary')"
            ref="ComparisonRevenue"
            :data="spendingSummary"
            data-item-total="total_amount"
            hide-divider
            :data-item-label="itemLabel"
            :action="{
                label: 'Go to Trends',
                iconClass: 'fa fa-chevron-right',
            }"
            @action="router.visit('/trends/income-expenses-graph')"
            @subitem-clicked="goToCategoryMonth"
        />
    </template>
</WidgetContainer>
</template>

