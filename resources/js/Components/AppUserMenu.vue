<template>
    <JetDropdown align="right" width="48" v-if="!context.isMobile">
        <template #trigger>
            <AppUserMenuButton
                :has-image="hasImage"
                :image-url="imageUrl"
                :user="user"
            />
        </template>

        <template #content>
            <!-- Account Management -->
            <div class="block px-4 py-2 text-xs text-gray-400">
                Manage Account
            </div>

            <JetDropdownLink :href="route('profile.show')">
                Profile
            </JetDropdownLink>

            <JetDropdownLink :href="route('api-tokens.index')" v-if="hasApiFeatures">
                API Tokens
            </JetDropdownLink>
            <div class="block px-4 py-2 text-xs text-gray-400">
                Exports
            </div>
            <AtDropdownLink :href="route('finance.export')"  target="_blank" as="a">
                Export transactions
            </AtDropdownLink>
            <AtDropdownLink :href="route('housing.occurrences.export')"  target="_blank" as="a">
                <section class="flex w-full">
                    <IMdiFile  class="mr-2" />
                    <span>
                        Export occurrence
                    </span>
                </section>
            </AtDropdownLink>
            <div class="block px-4 py-2 text-xs text-gray-400">
                Import
            </div>
            <AtDropdownLink as="button" target="_blank" @click="toggleImportModal()">
                Import
            </AtDropdownLink>

            <div class="border-t border-gray-100"></div>

            <!-- Authentication -->
            <form @submit.prevent="$emit('logout')">
                <JetDropdownLink as="button">
                    Log Out
                </JetDropdownLink>
            </form>
        </template>
    </JetDropdown>
    <AppUserMenuButton
        v-else
        :has-image="hasImage"
        :image-url="imageUrl"
        :user="user"
        @click="context.toggleOptionsModal()"
    />
</template>

<script setup lang="ts">
import JetDropdown from '@/Components/atoms/Dropdown.vue'
import JetDropdownLink from '@/Components/atoms/DropdownLink.vue'
import AppUserMenuButton from './AppUserMenuButton.vue';

import { useAppContextStore } from '@/store';
import { AtDropdownLink } from 'atmosphere-ui';
import { useImportModal } from '@/domains/transactions/useImportModal';

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

const context = useAppContextStore()


const { toggleModal: toggleImportModal } = useImportModal();
</script>
