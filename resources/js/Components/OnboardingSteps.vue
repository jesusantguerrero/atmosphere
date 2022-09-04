<template>
<div class="space-y-2 rounded border border-transparent"  ref="collapsable" :class="{'bg-base-lvl-3 shadow-xl border-base rounded border': isCollapsed}">
    <header class="px-4 py-2 flex justify-between cursor-pointer" @click="toggleCollapse()">
        <SectionTitle type="secondary"> Onboarding </SectionTitle>
        <i :class="icon" />
    </header>
    <div v-if="!isCollapsed" class="space-y-2">
        <div v-for="step in steps"
            class="border cursor-pointer group flex flex-nowrap space-x-4 bg-base-lvl-3 rounded-md p-4 shadow-xl"
            :key="step.name"
            >
            <div class="h-10 w-10  transition-all group-hover:border-primary group-hover:text-primary justify-center rounded-md border flex items-center"
                :class="[!step.complete ? 'text-primary border-primary shadow-md shadow-primary/20' : 'border-slate-300 text-slate-300']"
                >
                <i :class="step.icon" />
            </div>
            <div class="ml-4 w-full">
                <h1 class="text-body-1 font-bold"> {{ step.label }}</h1>
                <p class="text-body">
                    {{ step.description }}
                </p>
                <Link :href="step.link" class="mt-2 text-sm text-body-1 group-hover:text-primary/80"> {{ step.cta }}</Link>
            </div>
        </div>
    </div>
</div>
</template>

<script setup>
import { ref } from 'vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { useCollapse } from '@/composables/useCollapse';
import { Link } from '@inertiajs/inertia-vue3';
defineProps({
    steps: {
        type: Array,
        default() {
            return []
        }
    }
})

const collapsable = ref();
const { isCollapsed, toggleCollapse, icon } = useCollapse(collapsable, false);
</script>
