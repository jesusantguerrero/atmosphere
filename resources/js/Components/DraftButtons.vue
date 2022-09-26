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
import { Inertia } from "@inertiajs/inertia";


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
    Inertia.post('/transactions/remove-all-drafts')
        .then(response => {
            Inertia.reload()
        })
}

const approveTransactionAll = (transaction) => {
    Inertia.post(`/transactions/approve-all-drafts`).then(() => {
        Inertia.reload();
    })
}

const approveTransaction = (transaction) => {
    Inertia.post(`/transactions/${transaction.id}/approve`).then(() => {
        Inertia.reload();
    })
}

const removeTransaction = (transaction) => {
    Inertia.delete(`/transactions/${transaction.id}`).then(() => {
        Inertia.reload();
    })
}
</script>
