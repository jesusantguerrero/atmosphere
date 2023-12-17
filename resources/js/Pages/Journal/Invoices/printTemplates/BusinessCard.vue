<template>
  <div class="">
    <p class="font-bold capitalize" v-if="label">{{ label }}:</p>
    <section class="text-body">
      <div>{{ business.business_name }}</div>
      <div v-if="businessAddress" class="address-details">{{ businessAddress }}</div>
      <div v-if="businessPhone">{{ businessPhone }}</div>
      <div v-if="businessEmail">{{ businessEmail }}</div>
    </section>
  </div>
</template>

<script lang="ts" setup>
import { computed } from "vue";

const props = defineProps<{
  business: Record<string, string>;
  label?: string;
}>();

const businessAddress = computed(() => {
  return [
    props.business.business_street,
    props.business.business_city,
    props.business.business_state,
    props.business.business_country,
  ]
    .filter((value) => value)
    .join(", ");
});
const businessPhone = computed(() => {
  return props.business.business_phone;
});
const businessEmail = computed(() => {
  return props.business.business_email;
});
</script>

<style>
.address-details {
  max-width: 220px;
}
</style>
