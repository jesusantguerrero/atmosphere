<template>
    <article class="overflow-hidden bg-white rounded-lg shadow-md">
        <h4 class="p-4"> {{ item.name }} </h4>
        <section class="grid grid-cols-2 p-4">
            <div>
                <h5>This Month</h5>
                <h4>$181</h4>
                <small>spent so far</small>
            </div>
            <div>
                <h5>This Month</h5>
                <h4>$181</h4>
                <small>spent so far</small>
            </div>
        </section>
        <section class="grid grid-cols-3 p-4 bg-primary/10">
            <div>
                <h5>This Month</h5>
                <h4>$181</h4>
                <small>spent so far</small>
            </div>
            <div>
                <h4>$181</h4>
                <small>projected</small>
            </div>
            <div>
                <h4>$181</h4>
                <small>target</small>
            </div>
        </section>
    </article>
</template>

<script setup>
    defineProps({
        item: {
            type: Object,
            required: true
        }
    })
</script>
