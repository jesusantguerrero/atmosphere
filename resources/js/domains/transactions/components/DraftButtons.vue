<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
// @ts-expect-error: no definitions
import { AtButton } from "atmosphere-ui";

const props = defineProps<{ start?: string; end?: string }>();
const emit = defineEmits(['submitted'])
const { t } = useI18n();

const runAutomation = () => {
    axios.post('/api/automation/run-all')
        .then(()=> {
            emit('submitted')
        })
        .catch(() => {
        })
}

const removeAllDrafts = () => {
    if (!window.confirm(t('Clear these draft transactions? This cannot be undone.'))) return;
    router.post('/transactions/remove-all-drafts', { start: props.start, end: props.end }, {
        onSuccess() {
            emit('submitted')
        }
    })
}

const approveTransactionAll = () => {
    router.post(`/transactions/approve-all-drafts`, {} , {
        onSuccess() {;
            emit('submitted')
        }
    })
}
</script>


<template>
    <div class="flex items-center w-full space-x-2">
        <AtButton class="flex items-center h-10 space-x-2 text-primary" rounded @click="approveTransactionAll($event)">
            <i class="block mr-2 fa fa-check"></i> {{ $t('Approve') }}
        </AtButton>
        <AtButton class="flex items-center h-10 mr-2 space-x-2 text-primary" rounded @click="removeAllDrafts()">
            <i class="block mr-2 fa fa-times"></i> {{ $t('Remove') }}</AtButton>
        <AtButton class="flex items-center h-10 space-x-2 text-white bg-primary" rounded @click="runAutomation()">
            <i class="block fa fa-robot"></i>
        </AtButton>
    </div>
</template>

