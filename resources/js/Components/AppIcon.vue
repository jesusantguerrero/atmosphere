<script setup lang="ts">
import { computed } from "vue";

const props = defineProps({
  size: {
    type: String,
    validator(value) {
      const sizes = ["small", "medium", "large"];
      return sizes.includes(value);
    },
    default: "large",
  },
  letterClass: {
    type: String,
    default: "text-body-1/80",
  },
});

const sizes = {
  small: "14px",
  medium: "24px",
  large: "32px",
  huge: "195px",
};

const sizeClass = computed(() => {
  return sizes[props.size];
});

const isHuge = computed(() => props.size == "huge");
</script>

<template>
  <div class="flex flex-col">
    <img src="/logo.png" :style="{ height: sizeClass }" class="mx-auto" v-if="!isHuge" />
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 253.23 63.81"
      v-else
      :style="{ width: sizeClass }"
    >
      <defs></defs>
      <g id="Layer_1" data-name="Layer 1">
        <line class="cls-1" x1="243.25" y1="57.54" x2="207.25" y2="57.54" />
        <line class="cls-1" x1="245.25" y1="27.54" x2="245.25" y2="62.54" />
        <line class="cls-2" x1="249.81" y1="33.01" x2="225.47" y2="7.79" />
        <text class="cls-3" transform="translate(-2 62.55) scale(0.87 1)">
          <tspan class="cls-4">L</tspan>
          <tspan x="44.97" y="0">OGER</tspan>
        </text>
      </g>
    </svg>
    <small
      class="inline-block font-brand"
      :class="[isHuge ? 'text-sm mt-4' : 'text-xs text-body-1/80']"
      >The Digital Home</small
    >
  </div>
</template>



<style>
.cls-1,
.cls-2 {
  fill: none;
  stroke: #f37ea1;
}
.cls-1,
.cls-2,
.cls-3 {
  stroke-miterlimit: 10;
}
.cls-1 {
  stroke-width: 10px;
}
.cls-2 {
  stroke-width: 9px;
}
.cls-3 {
  font-size: 86.98px;
  fill: #626d73;
  stroke: #fff;
  font-family: Kanit-SemiBold, Kanit;
  font-weight: 600;
}
.cls-4 {
  letter-spacing: -0.01em;
}
</style>
