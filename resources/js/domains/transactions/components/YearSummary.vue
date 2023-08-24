<template>
  <div class="w-full comparison-card">
   
  </div>
</template>

<script setup lang="ts">
defineProps({
    title: {
        type: String
    },
    hideHeader: {
        type: Boolean
    },
    headerTemplate: {
        type: String,
        default: 'row'
    },
    type: {
      type: String,
      default: "bar"
    },
    data: {
      type: Object,
      required: true
    },
});


</script>
