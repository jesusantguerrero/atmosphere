<template>
    <div>
        <div class="px-6 py-3 overflow-hidden bg-white border-b border-gray-200 shadow-md sm:rounded-lg sm:px-20">
            <div class="mt-4 text-lg text-gray-600">
                Welcome to <span class="font-bold">Loger</span>
            </div>

            <div class="flex mt-6 text-gray-500">
                <div class="w-6/12">
                    <small class="font-bold">Current expenses</small>
                    <div class="text-2xl font-bold text-blue-800">DOP 5,000.00</div>
                </div>
                <div class="w-6/12">
                    <small class="font-bold">Monthly Budget</small>
                    <div class="text-2xl font-bold text-blue-800">DOP 25,000.00</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import JetApplicationLogo from '@/Jetstream/ApplicationLogo'

    export default {
        components: {
            JetApplicationLogo,
        },
    }
</script>
