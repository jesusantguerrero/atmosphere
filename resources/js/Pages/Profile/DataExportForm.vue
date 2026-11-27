<script setup lang="ts">
    import { useForm } from '@inertiajs/vue3'

    import JetActionSection from '@/Components/atoms/ActionSection.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'

    const form = useForm({});

    function requestExport() {
        form.post(route('user.data-export.store'), { preserveScroll: true });
    }
</script>

<template>
    <JetActionSection
        :title="$t('Download my data')"
        :description="$t('Get a copy of everything in the spaces you own.')"
    >
        <template #content>
            <div class="max-w-xl text-sm text-body-1/70">
                {{ $t('We put your transactions, accounts, budgets, meals, planner and tasks in a ZIP file with one spreadsheet per table, and email you the link. Passwords and connected-app credentials are left out.') }}
            </div>

            <div class="mt-5">
                <LogerButton
                    type="button"
                    variant="ghost"
                    :processing="form.processing"
                    :disabled="form.recentlySuccessful"
                    @click="requestExport"
                >
                    {{ form.recentlySuccessful ? $t('Preparing your data…') : $t('Download my data') }}
                </LogerButton>
            </div>
        </template>
    </JetActionSection>
</template>
