<template>
  <!-- <sec
  class="mx-auto mb-2 font-serif border-b-2 cursor-pointer invoice__signature w-96"
/> -->
  <section v-if="!editable">
    <p class="relative h-12 text-6xl border-b signature__input pt-auto firma">
      {{ currentSignature?.text }}
    </p>
    <article class="justify-center w-full text-center capitalize">
      <div class="font-bold">{{ label }}</div>
      <div>{{ $t("signature") }}</div>
    </article>
  </section>
  <section v-else class="relative py-2">
    <i class="absolute text-blue-300 fa fa-times -left-2 bottom-3"></i>
    <i class="absolute text-blue-300 fa fa-photos -left-2 bottom-3"></i>
    <button class=""></button>
    <input
      class="w-full px-2 text-6xl text-gray-700 border-b signature__input pt-auto firma focus:outline-none"
      spellcheck="false"
      ref="input"
      :disabled="disabled"
      :value="modelValue"
      @input="$emit('update:modelValue', $event?.target?.value)"
    />
  </section>
</template>

<script lang="ts" setup>
import { ref, computed } from "vue";

const props = defineProps<{
  signatures: Record<string, string>[];
  entity: { user_id: number };
  label?: string;
  disabled?: boolean;
  editable?: boolean;
  modelValue: string;
}>();

const emits = defineEmits(["update:modelValue"]);
const input = ref();
const focus = () => {
  input.value?.focus();
};

defineExpose({
  focus,
});
const currentSignature = computed(() => {
  return props.signatures?.find((signature) => signature.user_id == props.entity.user_id);
});
</script>

<style>
.firma {
  font-family: "Tangerine", cursive;
}

@media print {
  .firma {
    font-family: "Tangerine", cursive;
    font-size: 16px;
  }
}
</style>
