<script setup lang="ts">
defineProps<{
  isReverse?: boolean;
  invoiceTitle: string;
  invoiceNumber: string;
  invoiceDate: string;
  invoiceDueDate?: string;
  invoiceTitleClass: string;
}>();
</script>

<template>
  <article class="flex w-full">
    <section class="text-left">
      <h4 class="text-2xl font-bold uppercase" :class="invoiceTitleClass">
        {{ invoiceTitle }}
      </h4>
      <p>
        <span class="font-bold capitalize">{{ $t("invoice number") }} </span>
        {{ invoiceNumber }}
      </p>
      <p>
        <span class="font-bold capitalize">{{ $t("date") }} </span>
        {{ invoiceDate }}
      </p>
      <p v-if="invoiceDueDate">
        <span class="font-bold">{{ $t("due date") }} </span>
        {{ invoiceDueDate }}
      </p>
    </section>
  </article>
</template>
