<script setup lang="ts">
import { computed, reactive } from "vue";
import { useForm } from "@inertiajs/vue3";

import AdminTemplate from "./Partials/AdminTemplate.vue";
import Smtp from "@/Pages/Settings/Partials/MailDriverSmtp.vue";
import Mailgun from "@/Pages/Settings/Partials/MailDriverMailgun.vue";
import Ses from "@/Pages/Settings/Partials/MailDriverSes.vue";
import Basic from "@/Pages/Settings/Partials/MailDriverBasic.vue";

/**
 * Mail driver admin — configure the SMTP/Mailgun/SES driver Loger uses
 * for transactional email. System-wide setting, so it lives under
 * /admin/mail (not per-user Settings). The driver partials themselves
 * stay in Settings/Partials/ since they're pure form components with
 * no admin/user coupling and reused elsewhere.
 */

const props = defineProps<{
    settingData: Record<string, string>;
}>();

const mailConfigData = reactive({
    ...props.settingData,
});

const mailDrivers = ["smtp", "mail", "sendmail", "mailgun", "ses"].map((item) => ({
    id: item,
    label: item,
}));

const mailDriverComponent = computed(() => {
    switch (mailConfigData.mail_driver) {
        case "mailgun":
            return Mailgun;
        case "ses":
            return Ses;
        case "mail":
        case "sendmail":
            return Basic;
        case "smtp":
        default:
            return Smtp;
    }
});

const formData = useForm({
    mailDriver: mailConfigData.mail_driver ?? "smtp",
});

const saveEmailConfig = (data: Record<string, any>) => {
    formData
        .transform(() => ({
            ...data,
            encryption: data.encryption?.id,
            driver: data.driver?.id,
        }))
        .post("/admin/mail", {
            preserveScroll: true,
        });
};
</script>

<template>
    <AdminTemplate title="Mail driver" :show-back-button="true">
        <header class="mb-6">
            <h2 class="text-2xl font-bold text-body">{{ $t('Mail driver') }}</h2>
            <p class="text-sm text-body-1 mt-1 max-w-2xl">
                {{ $t('System-wide email configuration. Loger uses this driver to send every transactional and notification email — password resets, invitations, budget alerts.') }}
            </p>
        </header>

        <section class="rounded-lg bg-base-lvl-3 border border-base p-5 max-w-3xl">
            <component
                :is="mailDriverComponent"
                :config-data="mailConfigData"
                :is-saving="formData.processing"
                :mail-drivers="mailDrivers"
                @submit="saveEmailConfig"
            />
        </section>
    </AdminTemplate>
</template>
