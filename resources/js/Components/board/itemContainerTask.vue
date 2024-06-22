<script lang="ts" setup>
import { ref, computed, watch } from "vue";
// import Tracker from "./../timeTracker/tracker";

const props = defineProps<{
    task: Record<string, any>;
    tracker: Record<string, any>;
}>();

const emit = defineEmits(['update-item', 'item-clicked', 'item-deleted'])

const originalDuration = ref(null)

watch(() => props.tracker, (tracker?: Object) => {
 originalDuration.value = props.task.duration
}, { immediate: true });


const  isTracker = computed(() => {
    return props.tracker && props.tracker.timeEntry.item_id == props.task.id;
});

const durationFromMs = computed(() => {
    const currentDuration = isTracker.value ? tracker.value.duration || 0: 0;

    props.task.duration = originalDuration.value + currentDuration;
    return  '--'; //Tracker?.durationFromMs(this.task.duration);
});

const priorityText = computed(() =>  {
    const emojis = {
        "high": "🔥🔥🔥",
        "medium": "🔥🔥",
        "low": "🔥"
    }
    return props.task && emojis[props.task.priority] || "";
})
const updateTask = () => {
    emit('update-item', props.task)
}
</script>

<template>
<div  class="task-item">
    <div>
        <label class="checkbox-label">
            <span class="font-bold">
            <Link :href="`/plans/${task.board_id}`">
                <span class="font-bold">
                    <i class="mx-2 fas fa-layer-group"></i>
                    {{ task.stage }}
                </span>
            </Link>
        </span>
        <span>
            {{ task.title }}
        </span>
        </label>
    </div>
    <div class="flex items-center actions-container">
        <input
            type="checkbox"
            v-model="task.done"
            name=""
            class="checkbox-done"
            :id="task.id"
            @change="updateTask()"
        />
        <section v-if="isTracker">
            <ElTooltip class="item" effect="dark" :content="task.priority || 'none'" placement="left">
                <div class="inline-block ml-2 mr-4 priority-level">
                    <div class="priority-level__inner">

                    </div>
                </div>
            </ElTooltip>
            <span class="mr-2">
                {{ durationFromMs }}
            </span>
            <button @click="$emit('item-clicked', task)" class="play-button" v-if="!task.commit_date">
                <i class="fa" :class="[isTracker ? 'fa-pause' : 'fa fa-play']"/>
            </button>
        </section>

        <button @click="$emit('item-deleted', task)" class="ml-2 text-gray-300 hover:text-red-400">
            <i class="fa fa-trash"/>
        </button>
    </div>
</div>
</template>



<style lang="scss" scoped>
.priority-level {
    --color: red;
    border: solid 1px transparent;
    border-radius: 0.20rem;
    padding: 2px;
    transition: all ease .3s;

    &:hover {
        border: solid 1px var(--color);
    }
    &__inner {
        background-color: var(--color);
        width: 12px;
        height: 12px;
        border-radius: 0.20rem;
    }
}
</style>
