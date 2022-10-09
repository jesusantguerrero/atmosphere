<template>
<div
    class="mt-4 mb-10 overflow-hidden bg-white shadow-xl sm:rounded-lg"
>
    <h4 class="p-4 font-bold">Next Payments</h4>
    <div class="payment" v-for="payment in payments" :key="payment.id">
        {{ payment.name }}
    </div>
</div>
</template>

<script setup>
defineProps({
    payments: {
        type: Array,
        default() {
            return []
        }
    }
})
</script>
