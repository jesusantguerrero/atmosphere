<script lang="ts" setup>
import type { AssistantSection } from '@/stores/application.store';

const emit = defineEmits(['click'])
defineProps<{
    disabled: boolean;
    isSelected: boolean;
    tool: AssistantSection
}>()

</script>
<template>
<article>
        <button 
            type="button"
            class="flex items-center w-full h-10 px-3 py-3 text-lg font-semibold transition-all rounded-md cursor-pointer text-primary-gray/80"
            :class="{'bg-primary-light': isSelected }"
            @click="emit('click')"
            :label="tool.label"
            :testid="tool.name"
            :disabled="disabled"
        >
            <span class="w-10"    :class="{'text-primary': isSelected }">
                <component 
                    :is="tool.icon" 
                    :class="{'text-primary': isSelected }"
                />
            </span>
            <span class="inline-block pl-4 text-base ">
                {{ tool.label }}
            </span>
        </button>
        <slot v-if="isSelected" name="component" />
    </article>
</template>