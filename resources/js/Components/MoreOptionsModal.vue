<template>
  <modal
    :show="show"
    :max-width="maxWidth"
    :full-height="fullHeight"
    :closeable="closeable"
    v-slot:default="{ close }"
    @close="$emit('update:show', false)"
  >
    <div class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body flex-1">
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header class="font-bold border-b pb-4 text-lg">More Options</header>

        <div class="mt-2 space-y-2">
          <button
            v-ripple
            class="w-full py-2 font-bold capitalize hover:bg-base-lvl-2 overflow-hidden"
            v-for="option in options"
          >
            {{ option }}
          </button>
        </div>
      </div>
    </div>
  </modal>
</template>

<script setup>
import Modal from "@/Components/atoms/Modal.vue";

const props = defineProps({
  show: {
    default: false,
  },
  maxWidth: {
    default: "2xl",
  },
  closeable: {
    default: true,
  },
  categories: {
    type: Array,
    default: [],
  },
  accounts: {
    type: Array,
    default: [],
  },
  recurrence: {
    type: Boolean,
    default: false,
  },
  transactionData: {
    type: Object,
    default: () => ({}),
  },
  mode: {
    type: String,
    default: "income",
  },
  fullHeight: {
    type: Boolean,
  },
});

const emit = defineEmits(["close", "saved"]);

const options = [
  "Profile",
  "Api Tokens",
  "Accounts",
  "Labels",
  "Imports",
  "Exports",
  "Support",
  "About",
  "Help Center",
  "Terms and policies",
  "Settings",
  "Logout",
];
</script>
