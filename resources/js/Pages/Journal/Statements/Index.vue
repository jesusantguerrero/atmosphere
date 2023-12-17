<script setup lang="ts">
import AppLayout from "@/Components/templates/AppLayout.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
  categories: {
    type: Array,
  },
});
</script>

<template>
  <AppLayout title="Reportes">
    <div class="w-full py-10 mx-auto mt-8 sm:px-6 lg:px-8 print:w-[210mm] print">
      <div
        class="w-full px-5 py-5 mb-10 bg-white rounded-md shadow-md print:w-[210mm] print"
      >
        <div
          class="mt-10 divide-y divide-base-lvl-1 text-body-1 items print:w-[210mm] print"
        >
          <div
            v-for="(category, categoryName) in categories"
            :key="categoryName"
            class="flex justify-between py-2"
          >
            <div class="w-full">
              <h4 class="text-xl font-bold text-primary">{{ category.label }}</h4>
              <p class="text-body-1">{{ category.description }}</p>
            </div>
            <div class="w-full space-y-2">
              <Link
                :href="report.url"
                v-for="(report, reportName) in category.reports"
                :key="reportName"
                class="block px-5 py-4 transition rounded-md cursor-pointer bg-gray-50 hover:bg-gray-100"
              >
                <h5 class="text-lg font-bold text-secondary">
                  {{ report.label }}
                </h5>
                <p>{{ report.description }}</p>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style lang="scss">
@media print {
  .recibo-body {
    width: 210mm;
    border: none;
    overflow: hidden;
    border-bottom: 2px dashed #ddd;
    border-left: 1px dashed #ddd;
    border-right: 1px dashed #ddd;
    -webkit-print-color-adjust: exact;
  }
}
</style>
