<template>
<div class="space-y-2 border border-transparent rounded bg-base-lvl-3 shadow-xl"  ref="collapsable" :class="{'pb-4': isCollapsed}">
    <header class="flex items-center justify-between px-4 py-2 cursor-pointer" @click="toggleCollapse()">
        <SectionTitle type="secondary"> Onboarding </SectionTitle>
        <i :class="icon" />
    </header>
    <div v-if="!isCollapsed" class="space-y-2">
        <div v-for="step in steps"
            class="flex p-4 space-x-4 border rounded-md cursor-pointer border-base-lvl-3/80 group flex-nowrap bg-base-lvl-3"
            :key="step.name"
            >
            <div class="flex items-center justify-center w-10 h-10 transition-all border rounded-md group-hover:border-primary group-hover:text-primary"
                :class="[!step.complete ? 'text-primary border-primary  shadow-primary/20' : 'border-slate-300 text-slate-300']"
                >
                <i :class="step.icon" />
            </div>
            <div class="w-full ml-4">
                <h1 class="font-bold text-body-1"> {{ step.label }}</h1>
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
