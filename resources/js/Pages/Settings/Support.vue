<template>
    <AppLayout title="Settings - About">
        <template #header>
            <SettingsSectionNav />
        </template>
        <div class="h-auto py-12 pt-32 mx-auto space-y-4 max-w-7xl sm:px-6 lg:px-8">
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
import {  reactive } from "vue";
import AppLayout from "@/Components/templates/AppLayout.vue";
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";
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
