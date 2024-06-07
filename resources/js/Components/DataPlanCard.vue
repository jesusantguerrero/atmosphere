<template>
  <div class="bg-white px-5 py-10 mb-5 mx-2 rounded-md shadow-md rounded-md flex">
    <div class="w-4/12">
      <div class="mx-auto">
        <div class="prose prose-xl">
          <h5 class="mb-2">Plan Details</h5>
          <div class="text-3xl">
            <span class="text-primary font-bold">
              {{ plan.plan?.display_name ?? plan.name }}
            </span>

            <div class="text-2xl text-gray-400">Your plan information</div>
            <div class="text-sm mt-10 grid grid-cols-2 gap-2 w-full" v-if="plan.plan">
              <span
                v-for="feature in plan.plan.features"
                class="capitalize text-secondary font-bold"
              >
                {{ feature }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center w-4/12">
      <div class="prose prose-xl px-5 py-2 my-2 rounded-md mx-auto">
        <div v-if="plan.trials_ends_at">
          <h4>Days left</h4>
          <p>
            {{ plan.trials_ends_at }}
          </p>
        </div>

        <div class="mt-10 mb-2">
          <h5>Plan Details</h5>
          <p class="text-2x1 text-gray-400">
            <span> <i class="fa fa-users"></i> 1 member(s) </span>
            <span class="ml-2"> <i class="fa fa-users"></i> 1 team(s) </span>
          </p>
        </div>
      </div>
    </div>

    <div class="text-center w-4/12">
      <button
        class="bg-gray-700 text-white px-5 py-2 inline-block rounded-md mr-2"
        v-if="status == 'active'"
        @click="$emit('suspend')"
      >
        Suspend
      </button>
      <button
        class="bg-green-500 text-white px-5 py-2 inline-block rounded-md mr-2"
        v-if="status == 'suspended'"
        @click="$emit('reactivate')"
        :disabled="disabled"
      >
        Reactivate
      </button>
      <button
        class="bg-red-500 text-white px-5 py-2 inline-block rounded-md"
        v-if="['active', 'suspended'].includes(status)"
        @click="$emit('cancel')"
        :disabled="disabled"
      >
        cancel
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  plan: Record<string, any>;
  disabled?: boolean;
}>();

const status = computed(() => {
  return props.plan.status && props.plan.status.toLowerCase();
});
</script>
