<template>
    <AppLayout title="Settings - About">
        <template #header>
            <SettingsSectionNav />
        </template>
        <div class="h-auto py-12 pt-32 mx-auto space-y-4 max-w-7xl sm:px-6 lg:px-8">
            <ProductInfo
                app-version="1.0.0"
                :sections="state.sections"
                developer="Jesus Guerrero"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from "vue";
import AppLayout from "@/Components/templates/AppLayout.vue";
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";
import ProductInfo from "@/Components/ProductInfo.vue";

const props = defineProps({
    services: {
        type: Array,
        default() {
            return [];
        }
    },
    integrations: {
        type: Array,
        default() {
            return [];
        }
    },
    recipes: {
        type: Array,
        default() {
            return [];
        }
    },
    tasks: {
        type: Array,
        default() {
            return [];
        }
    },
})

const state = reactive({
    sections: {
        info: "Information",
        tutorial: "Tutorial",
        thanks: "Thanks to",
        licence: "License",
        support: "Support",
    },
});

</script>

<style lang="scss">
    .app-service__integration {
        @apply bg-base-lvl-1 text-gray-500 my-2 cursor-pointer px-5 py-3 font-bold;
        @apply border-2 border-gray-300 rounded-md;
        width: 150px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
</style>
