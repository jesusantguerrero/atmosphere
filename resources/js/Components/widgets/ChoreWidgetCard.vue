<script setup lang="ts">
import { computed } from "vue";

// Props
const props = withDefaults(defineProps<{
    board: IBoard;
    color: string;
}>(), {
  color:  "#D8D7F1", // Default color
});

// Dynamically set card styles
const cardStyle = computed(() => ({
  backgroundColor: props.color,
}));
</script>

<template>
    <section class="wrapper">
        <div class="bg-brand shadow-md rounded-lg p-4 text-center">
          <!-- Profile Image -->
          <div class="w-20 h-20 mx-auto rounded-full text-4xl bg-light flex items-center justify-center  overflow-hidden bg-white shadow py-4">
            <!-- Replace with actual image -->
            {{ board.name.at(0)}}
          </div>
          <!-- Name -->
          <h3 class="mt-4 text-darker font-bold text-lg capitalize">{{ board.name }}</h3>
          <!-- School -->
          <p class="text-[#6E6E6E] text-sm">{{ board.description ?? 'N/D' }}</p>
          <!-- Percentage -->
          <p class="mt-2 text-2xl font-bold text-[#4A3F94]">{{ board.template}}</p>
          <!-- Rank Badge -->
          <span class="mt-1 inline-block bg-light text-white text-sm px-4 py-1 rounded-full">
            {{ board.itemsCount ?? 0 }}
          </span>
        </div>
    </section>
</template>


<style lang="scss" scoped>
.wrapper {
    --custom-color: v-bind(color)
}

.bg-brand {
    background-color: var(--custom-color);
  }

  /* 50% blue, 50% white */
  .bg-light {
    background-color: color-mix(in srgb, var(--custom-color), white);
  }

  /* 25% blue, 75% white */
  .bg-blue-lighter {
    background-color: color-mix(in srgb, blue, white 75%);
  }

  /* 50% blue, 50% black */
  .bg-blue-dark {
    background-color: color-mix(in srgb, blue, black);
  }

  /* 25% blue, 75% black */
  .text-darker {
    color: color-mix(in srgb, var(--custom-color), black 75%);
  }
</style>
