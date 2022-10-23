<template>
    <JetDropdown align="right" width="60">
        <template #trigger>
            <span class="inline-flex rounded-md">
                <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 transition border border-transparent rounded-md text-body hover:bg-base-lvl-2 hover:text-body/80 focus:outline-none focus:bg-base-lvl-1">
                    {{ label }}

                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </span>
        </template>

        <template #content>
            <div class="w-60">
                <template v-for="(item, itemKey) in items" :key="itemKey">
                    <div class="block px-4 py-2 text-xs text-body-1" >
                        {{ item.label }}
                    </div>

                    <!-- Team Settings -->
                    <slot :name="kebabCase(itemKey)">
                        <div>
                            <JetDropdownLink :href="section.url" v-for="(section, key) in item.sections" :key="key">
                                {{ section.label }}
                            </JetDropdownLink>
                        </div>
                    </slot>
                    <div class="border-t border-gray-100"></div>
                </template>
            </div>
        </template>
    </JetDropdown>
</template>

<script setup>
import JetDropdown from '@/Components/atoms/Dropdown.vue'
import JetDropdownLink from '@/Components/atoms/DropdownLink.vue'
import { kebabCase } from 'lodash';

defineProps({
    items: {
        type: Object,
        required: true
    },
    label: {
        type: String,
        required: true
    },
    disabled: {
        type: Boolean
    }
})

</script>
