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
            <!-- Account -->
            <div class="block px-4 py-2 text-xs text-body-1/70">
                {{ $t('Account') }}
            </div>

            <JetDropdownLink :href="route('profile.show')">
                <span class="flex items-center gap-2"><IMdiAccountOutline /> {{ $t('Profile') }}</span>
            </JetDropdownLink>

            <JetDropdownLink :href="route('api-tokens.index')" v-if="hasApiFeatures">
                <span class="flex items-center gap-2"><IMdiKeyOutline /> {{ $t('API Tokens') }}</span>
            </JetDropdownLink>

            <!-- Data -->
            <div class="block px-4 py-2 text-xs text-body-1/70">
                {{ $t('Data') }}
            </div>
            <AtDropdownLink as="button" target="_blank" @click="toggleImportModal()">
                <span class="flex items-center gap-2"><IMdiTrayArrowDown /> {{ $t('Import') }}</span>
            </AtDropdownLink>
            <AtDropdownLink :href="route('finance.export')" target="_blank" as="a">
                <span class="flex items-center gap-2"><IMdiTrayArrowUp /> {{ $t('Export transactions') }}</span>
            </AtDropdownLink>
            <AtDropdownLink :href="route('housing.occurrences.export')" target="_blank" as="a">
                <span class="flex items-center gap-2"><IMdiFileExportOutline /> {{ $t('Export occurrences') }}</span>
            </AtDropdownLink>

            <div class="border-t border-base-lvl-2"></div>

            <!-- Authentication -->
            <form @submit.prevent="$emit('logout')">
                <JetDropdownLink as="button">
                    <span class="flex items-center gap-2"><IMdiLogout /> {{ $t('Log Out') }}</span>
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
