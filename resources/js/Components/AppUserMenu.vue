<template>
    <JetDropdown align="right" width="48">
        <template #trigger>
            <button v-if="hasImage" class="flex text-sm transition border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 active:slate-600" >
                <img class="object-cover w-8 h-8 rounded-full" :src="imageUrl" :alt="user.name" />
            </button>

            <span v-else class="inline-flex rounded-md">
                <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 transition border border-transparent rounded-md text-body hover:text-gray-100 hover:bg-base-lvl-1 focus:outline-none focus:bg-base-lvl-2">
                    {{ user.name }}

                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </span>
        </template>

        <template #content>
            <!-- Account Management -->
            <div class="block px-4 py-2 text-xs text-gray-400">
                Manage Account
            </div>

            <jet-dropdown-link :href="route('profile.show')">
                Profile
            </jet-dropdown-link>

            <jet-dropdown-link :href="route('api-tokens.index')" v-if="hasApiFeatures">
                API Tokens
            </jet-dropdown-link>

            <div class="border-t border-gray-100"></div>

            <!-- Authentication -->
            <form @submit.prevent="$emit('logout')">
                <jet-dropdown-link as="button">
                    Log Out
                </jet-dropdown-link>
            </form>
        </template>
    </JetDropdown>
</template>

<script setup>
import JetDropdown from '@/Components/atoms/Dropdown.vue'
import JetDropdownLink from '@/Components/atoms/DropdownLink.vue'

defineProps({
    hasImage: {
        type: Boolean
    },
    hasApiFeatures: {
        type: Boolean
    },
    imageUrl: {
        type: String
    },
    user: {
        type: Object
    }
})
</script>
