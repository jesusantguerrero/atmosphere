<script setup lang="ts">

import { ref } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AppLayout from "../../Components/templates/AppLayout.vue";
import SectionNav from "@/Components/molecules/SectionNav.vue";
import SectionCard from "@/Components/molecules/SectionCard.vue";

const props = defineProps({
  tabName: {
    type: String,
    default: "business",
  },
});

const handleClick = (paneName: string) => {
  router.replace(`/settings?tab=${paneName}`);
};

const currentTab = ref("business");

const tabs = ref({
  business: {
    label: "Organización",
    sections: [
      ["Company settings", "/settings/business"],
      ["Invoicing", "/settings/invoice"],
      // ["Fee Collection", "/settings/fee"],
      "",
      // ["Theme", "/settings/theme"],
      // ["Region", "/settings/region"],
    ],
  },
  // payments: {
  //   label: "Pagos",
  //   sections: [
  //     // ["Paypal", '/settings/paypal'],
  //     ["Modal de pago", "/settings/payment"],
  //   ],
  // },
  // integrations: {
  //     label: "Integrations",
  //     sections: [
  //         ["Import Data", 'settings/integrations/import-data'],
  //         ["Zen", '/integrations'],
  //         ["Gmail", '/integrations'],
  //         ["Calendar", '/integrations'],
  //     ]
  // },
  // notifications: {
  //   label: "Notifications",
  //   sections: [
  //     ["Notificationes en app", { name: "business" }],
  //     ["Correo Electronico", { name: "business" }],
  //     "",
  //     ["Propiedades", { name: "business" }],
  //     ["Prestamos", { name: "region" }],
  //   ],
  // },
  account: {
    label: "Cuenta y Seguridad",
    sections: [
      ["User info", "/user/profile"],
      // ["Plan", "/billing/upgrade"],
      // ["Billing", "/billing"],
    ],
  },
});

currentTab.value = props.tabName;
</script>

<template>
  <AppLayout title="Configuracion">
    <template #header>
      <SectionNav
        :sections="tabs"
        v-model="currentTab"
        @update:modelValue="handleClick"
      />
    </template>

    <div class="w-full h-auto py-12 mx-auto sm:px-6 lg:px-8 flex">
      <div class="text-left section_container">
        <div v-for="section in tabs[currentTab].sections" :key="section" class="">
          <template v-if="section && section.length">
            <Link
              :href="section[1]"
              class="flex justify-between w-full px-2 py-2 font-bold text-gray-400 transition transform bg-white border hover:text-blue-400 hover:shadow-md hover:border-blue-400"
            >
              <div>
                {{ $t(section[0]) }}
              </div>

              <div :href="section[1]" v-if="section[1]">
                <i class="fa fa-chevron-right"></i>
              </div>
            </Link>
          </template>
          <div v-else class="mb-4"></div>
        </div>
      </div>
      <SectionCard
        class="ml-8 bg-base-lvl-3 px-6"
        title="Mail Configuration"
        description="Below is the form for Configuring Email driver for sending emails from the app. You can also configure third party providers like Sendgrid, SES etc."
      >
        <template #content>
          <slot />
        </template>
      </SectionCard>
    </div>
  </AppLayout>
</template>
