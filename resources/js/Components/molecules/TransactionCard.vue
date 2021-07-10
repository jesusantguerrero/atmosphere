<template>
  <div class="flex justify-between px-5 py-2">
    <div class="flex space-x-3">
        <div class="w-20 px-5 py-3 font-bold text-center rounded-md bg-gray-50">
            MC
        </div>
        <div>
            <h4 class="font-bold"> {{ title }}</h4>
            <small class="text-sm"> {{ subtitle }}</small>
        </div>

    </div>
    <div class="flex space-x-5">
        <div>
            <h4 class="font-bold"> {{ value}}</h4>
            <small class="text-sm"> {{ status }}</small>
        </div>
        <div v-if="markAsPaid">
            Mark as Paid
        </div>
    </div>
</div>
</template>

<script>
export default {
    props: {
        title: String,
        subtitle: String,
        value: String,
        status: String,
        markAsPaid: Boolean
    }
}
</script>
