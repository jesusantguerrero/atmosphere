<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
// @ts-ignore
import { AtField } from "atmosphere-ui";
import { getStatus, getStatusColor, getStatusIcon } from "@/Modules/invoicing/constants";

import AccountingSectionNav from "@/Pages/Journal/Partials/AccountingSectionNav.vue";
import InvoiceSimple from "./printTemplates/Index.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";
import InvoicePaymentOptions from "@/Components/templates/InvoicePaymentOptions.vue";
import AppButton from "@/Components/shared/AppButton.vue";

import { formatDate, formatMoney } from "@/utils";
import { getInvoiceTypeUrl } from "./utils";
import { ref } from "vue";
import { IInvoiceWithRelations } from "@/Modules/invoicing/entities";
import SignatureModal from "./Partials/SignatureModal.vue";
import { useToggleModal } from "@/Modules/_app/useToggleModal";

withDefaults(defineProps<{
  user: Record<string, string>
  invoice: IInvoiceWithRelations;
  businessData: Record<string,string>;
  type: string;
  invoiceTemplate: string
}>(), {
  invoiceTemplate: 'invoice-simple'
});


const isModalPrintOpen = ref(false);
const print = () => {
  const modalInvoice = document.getElementById("invoice-content")
  const cloned = modalInvoice?.cloneNode(true)
  let section = document.getElementById("print")
  isModalPrintOpen.value = true;

  if (!section) {
     section  = document.createElement("div")
     section.id = "print"
     document.body.appendChild(section)
  }

  section.innerHTML = "";
  if (cloned) {
    section.appendChild(cloned);
    window.print();
  }
}

const { isOpen, openModal, data, closeModal } = useToggleModal('signature-modal')
</script>


<template>
  <AppLayout :title="invoice.concept">
    <template #header>
      <AccountingSectionNav>
        <template #actions>
          <AppButton @click="router.visit(getInvoiceTypeUrl(invoice))"
            variant="inverse"
            v-if="false">
            {{ $t("edit invoice") }}
          </AppButton>
          <AppButton variant="neutral" @click="print()">
            <IMdiPrinter />
          </AppButton>
          <InvoicePaymentOptions :invoice="invoice" class="py-2" />
        </template>
      </AccountingSectionNav>
    </template>

    <div class="py-10 mx-auto space-y-4 max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between px-5 py-1 border rounded-md space bg-base-lvl-3">
          <section class="flex space-x-4">
            <AtField label="Estatus">
                <p class="font-bold text-md" :class="getStatusColor(invoice.status)">
                  <i :class="getStatusIcon(invoice.status)" />
                  {{ getStatus(invoice.status) }}
                </p>
            </AtField>
            <AtField label="Cliente">
                <p class="font-bold text-primary text-md">
                  {{ invoice.client.display_name }}
                </p>
            </AtField>
          </section>

          <section class="flex space-x-4">
            <AtField label="Monto Adeudado">
                <p class="text-md">
                  {{ formatMoney(invoice.debt) }}
                </p>
            </AtField>
            <AtField label="Fecha limite">
                <p class="font-bold text-md text-primary">
                  {{ formatDate(invoice.due_date) }}
                </p>
            </AtField>
          </section>
        </div>

        <div class="flex justify-between px-5 py-1 border rounded-md space bg-base-lvl-3">
          <section>
            Detalles de creacion
            <Link :href="`/transactions/${invoice.transaction?.id}`" class="underline cursor-pointer text-primary underline-offset-1">Asiento Contable</Link>
          </section>
          <section>
            Detalles de cobro
            <p>
              <span>Deuda: {{ formatMoney(invoice.debt) }} — Recibir pago manual </span>
              <div v-for="payment in invoice.payments">
                Recibido en {{ payment.date }}
              </div>
            </p>
          </section>
        </div>

        <InvoiceSimple
          :user="user"
          :imageUrl="$page.props.user.current_team?.profile_photo_url"
          :type="type"
          :business-data="businessData"
          :invoice-data="invoice"
          id="invoice-content"
          @signature-clicked="openModal({
            data: {
              signatures: invoice.signatures,
              entity: invoice,
            },
            isOpen: true,
          })"
        />

        <SignatureModal
            v-if="isOpen"
            v-model:is-open="isOpen"
            v-bind="data"
            :endpoint="`/invoices/${invoice.id}/sign`"
            @saved="closeModal"
        />
    </div>
  </AppLayout>
</template>
