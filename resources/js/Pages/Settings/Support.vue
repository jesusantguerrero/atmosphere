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
                <main class="text-center">
                    <h4 class="font-bold text-2xl"> Support the application</h4>
                    <section class="mt-2">
                        <p>Loger and I personally are thankful for open source and there will be always a free self-hosted version of the project available. But
                            if Loger has somehow helped you and you want to express your gratitude consider upgrading to a pro version or buy a coffee
                        </p>

                        <div class="mt-8 space-y-4">
                            <section class="bg-base-lvl-2 rounded-md py-4">
                                Pro Version (coming soon)
                            </section>
                            <section class="bg-base-lvl-2 rounded-md py-4">
                                Buy me a coffee
                            </section>
                            <section class="bg-base-lvl-2 rounded-md py-4">
                                Paypal
                            </section>
                        </div>
                        <p class="mt-4">
                            Keep sharing the word about how budgeting has contributed to your financial health.
                        </p>
                    </section>
                </main>
            </ProductInfo>
        </div>
    </AppLayout>
</template>

<script setup>
import { nextTick, reactive } from "vue";
import { Inertia } from '@inertiajs/inertia';
import { AtButton } from "atmosphere-ui";
import AppLayout from "@/Layouts/AppLayout.vue";
import AutomationModal from '@/Components/AutomationModal.vue';
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";
import AppIcon from "@/Components/AppIcon.vue";
import ProductInfo from "@/Components/ProductInfo.vue";

const state = reactive({
    sections: {
        info: "Information",
        tutorial: "Tutorial",
        thanks: "Thanks to",
        licence: "License",
    },
});
</script>
