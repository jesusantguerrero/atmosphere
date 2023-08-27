<script setup lang="ts">
import { router } from "@inertiajs/vue3";
// @ts-expect-error: no definitions
import { AtButton } from "atmosphere-ui";

const emit = defineEmits(['submitted'])

const runAutomation = () => {
    axios.post('/api/automation/run-all')
        .then(()=> {
            emit('submitted')
        })
        .catch(() => {
            console.log(error)
        })
}

const removeAllDrafts = () => {
    router.post('/transactions/remove-all-drafts', {} ,{
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
            <i class="block mr-2 fa fa-check"></i> Approve
        </AtButton>
        <AtButton class="flex items-center h-10 mr-2 space-x-2 text-primary" rounded @click="removeAllDrafts()">
            <i class="block mr-2 fa fa-times"></i> Remove</AtButton>
        <AtButton class="flex items-center h-10 space-x-2 text-white bg-primary" rounded @click="runAutomation()">
            <i class="block fa fa-robot"></i>
        </AtButton>
    </div>
</template>

