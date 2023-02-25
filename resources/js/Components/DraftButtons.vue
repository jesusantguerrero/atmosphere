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

<script setup>
import { AtButton } from "atmosphere-ui";
import { router } from "@inertiajs/vue3";


const runAutomation = () => {
    axios.post('/api/automation/run-all')
        .then(response => {
            console.log(response.data)
        })
        .catch(error => {
            console.log(error)
        })
}

const removeAllDrafts = () => {
    router.post('/transactions/remove-all-drafts', {
        onSuccess() {
            router.reload();
        }
    })
}

const approveTransactionAll = (transaction) => {
    router.post(`/transactions/approve-all-drafts`, {
        onSuccess() {
            router.reload();
        }
    })
}

const approveTransaction = (transaction) => {
    router.post(`/transactions/${transaction.id}/approve`, {
        onSuccess() {
            router.reload();
        }
    })
}

const removeTransaction = (transaction) => {
    router.delete(`/transactions/${transaction.id}`, {
        onSuccess() {
            router.reload();
        }
    })
}
</script>
