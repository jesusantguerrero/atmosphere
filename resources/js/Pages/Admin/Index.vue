<script lang="ts" setup>
import { router } from "@inertiajs/core";

import AppButton from "@/Components/atoms/LogerButton.vue";
import WelcomeWidget from "@/Components/WelcomeWidget.vue";
import DashboardTemplate from "./Partials/AdminTemplate.vue";

const props = defineProps({
  user: {
    type: Object,
  },
  revenue: {
    type: Object,
    default() {
      return {
        previousYear: {
          values: [],
        },
        currentYear: {
          values: [],
        },
      };
    },
  },
  stats: {
    type: Object,
  },
  cashOnHand: {
    type: Object,
  },
  totals: {
    type: Object,
  },
  accounts: {
    type: Array,
  },
  nextInvoices: {
    type: Array,
  },
  paidCommissions: {
    type: Number,
  },
});

const appStats = [
  {
    label: "Total usuarios",
    value: props.stats?.users || 0,
  },
  {
    label: "Empresas",
    icon: "fa-money",
    value: `${props.stats?.teams || 0}`,
  },
  {
    label: "Propiedades",
    icon: "fa-money",
    value: `${props.stats?.properties || 0}`,
  },
];

const propertyStats = [
  {
    label: "Units",
    icon: "fa-money",
    value: `${props.stats?.units || 0}`,
  },
  {
    label: "Properties",
    icon: "fa-money",
    value: `${props.stats?.properties || 0}`,
  },
];

const comparisonRevenue = {
  headers: {
    gapName: "Year",
    previous: props.revenue.previousYear.total,
    current: props.revenue.currentYear.total,
  },
  options: {
    chart: {
      id: "vuechart-example",
    },
    stroke: {
      curve: "smooth",
    },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 0,
      blur: 1,
      opacity: 0.5,
    },
    colors: ["#fa6b88", "#80CDFE"],
  },
  series: [
    {
      name: "previous year",
      data: props.revenue.previousYear.values.map(
        (item: Record<string, any>) => item.total
      ),
    },
    {
      name: "current year",
      data: props.revenue.currentYear.values.map(
        (item: Record<string, any>) => item.total
      ),
    },
  ],
};
</script>

<template>
  <DashboardTemplate :user="user">
    <header class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
      <WelcomeWidget
        message="Estadisticas de usuarios"
        class="text-body-1 w-full shadow-md"
        :cards="appStats"
      />
    </header>

    <section class="flex flex-col mt-8 lg:space-x-4 lg:flex-row">
      <section class="lg:w-7/12 space-y-4">
        <IncomeSummaryWidget
          class="order-2 mt-4 lg:w-full lg:mt-0 lg:order-1 shadow-md"
          :style="{ height: '350px' }"
          :chart="comparisonRevenue"
          :headerInfo="comparisonRevenue.headers"
          :sections="accounts"
        />

        <article class="rounded-md bg-base-lvl-3 shadow-md">
          <header class="flex justify-between px-5 py-2 text-body-1">
            <h4 class="text-xl font-bold">Proximos pagos</h4>
            <AppButton
              variant="inverse"
              @click="router.visit(route('properties.create'))"
            >
              {{ $t("add rent") }}
            </AppButton>
          </header>
          <section class="px-5 space-y-4">
            <InvoiceCard v-for="invoice in nextInvoices" :invoice="invoice" />
          </section>
        </article>
      </section>

      <article class="order-1 space-y-5 lg:w-5/12 lg:order-2">
        <WelcomeWidget
          message="Propiedades"
          class="text-body-1 w-full shadow-md"
          size="small"
          :cards="propertyStats"
        />

        <IncomeSummaryWidget
          class="order-2 mt-4 lg:w-full lg:mt-0 lg:order-1 shadow-md"
          :style="{ height: '350px' }"
          :chart="comparisonRevenue"
          :headerInfo="comparisonRevenue.headers"
        />

        <WelcomeWidget
          message="Recent units"
          class="text-body-1 w-full shadow-md"
          :cards="propertyStats"
          v-if="false"
        >
          <template #content>
            <div class="rounded-md h-44 w-full bg-base-lvl-2 p-4 mb-4">
              <h4 class="font-bold">DOP 5000</h4>
              <p class="mt-4"><IconMarker /> <span>Address</span></p>
              <p class="space-x-4 mt-2">
                <span>3 Dormitorios</span><span>1 Baño</span><span>300 mts</span>
              </p>
            </div>
          </template>
        </WelcomeWidget>
      </article>
    </section>
  </DashboardTemplate>
</template>
