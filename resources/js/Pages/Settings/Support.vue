<template>
    <AppLayout title="Settings - About">
        <template #header>
            <SettingsSectionNav />
        </template>
        <div class="py-12 pt-32 h-auto mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-4">
            <ProductInfo
                app-version="1.0.0"
                developer="Jesus Guerrero"
            >
                <SettingAppSupport />
            </ProductInfo>
        </div>
    </AppLayout>
</template>

<script setup>
import { nextTick, reactive } from "vue";
port { AtButton } from "atmosphere-ui";
import AppLayout from "@/Components/templates/AppLayout.vue";
import AutomationModal from '@/Components/AutomationModal.vue';
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";
import AppIcon from "@/Components/AppIcon.vue";
import ProductInfo from "@/Components/ProductInfo.vue";
import SettingAppSupport from "@/Components/templates/SettingAppSupport.vue";

const state = reactive({
    sections: {
        info: "Information",
        tutorial: "Tutorial",
        thanks: "Thanks to",
        licence: "License",
    },
});
</script>
