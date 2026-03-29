<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold dark:text-white">Keyboard Shortcuts</h2>
        <button
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        >
          <i class="fa fa-times"></i>
        </button>
      </div>

      <div class="space-y-3">
        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">New Task</span>
          <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">
            {{ modKey }}+N
          </kbd>
        </div>

        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">Focus Search</span>
          <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">
            {{ modKey }}+K
          </kbd>
        </div>

        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">Toggle View</span>
          <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">
            {{ modKey }}+V
          </kbd>
        </div>

        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">Delete Selected</span>
          <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">
            Delete
          </kbd>
        </div>

        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">Close Modal</span>
          <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">
            Esc
          </kbd>
        </div>

        <div class="flex items-center justify-between py-2 border-b dark:border-gray-700">
          <span class="text-gray-700 dark:text-gray-300">Show Help</span>
          <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">
            ?
          </kbd>
        </div>
      </div>

      <div class="mt-6 text-center">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700"
        >
          Got it!
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  show: boolean;
  modKey: string;
}>();

defineEmits(['close']);
</script>
