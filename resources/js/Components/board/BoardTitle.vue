<script setup lang="ts">
import { ref, nextTick } from 'vue';

interface Board {
    id: number;
    name: string;
}

interface Automation {
    id: number;
    service_logo: string;
    name: string;
}

const props = defineProps<{
    board: Board
    automations: Automation[]
}>();

const emit = defineEmits(['saved', 'run-automation']);

const isEditMode = ref();
const boardNameInputRef= ref<HTMLInputElement>()
function toggleEditMode() {
    isEditMode.value = !isEditMode.value;
    nextTick(() => {
        boardNameInputRef.value?.focus()
    });
};

const boardName = ref(props.board.name)
const checkChanges = (shouldToggle: boolean) => {
    if (boardName.value !== props.board.name) {
        const changes = {...props.board, name: boardName.value}
        emit('saved', changes)
    }
    if (shouldToggle) {
        toggleEditMode()
    }
}
</script>

<template>
<div class="flex justify-between mr-2">
    <span class="w-full px-2 text-3xl font-bold border border-transparent rounded-md hover:border-slate-300"
    v-if="!isEditMode"
    @click="toggleEditMode()"
    >
        {{ boardName }}
    </span>
    <input
        v-else
        v-model="boardName"
        id="board-name"
        ref="boardNameInputRef0"
        type="text"
        class="inline-block w-full px-2 text-2xl font-bold border rounded-md focus:outline-none focus:border-purple-500"
        @blur="checkChanges(true)"
        @keypress.enter="checkChanges(true)"
    />
    <div v-if="automations?.length">
        <span
            class="automation"
            v-for="automation in automations"
            :key="`automation-${automation.id}`"
            @click="emit('run-automation', automation.id)"
        >
            <img :src="automation.service_logo" v-if="automation.service_logo" class="automation-logo">
            <div v-else>
                {{ automation.name[0] }}

            </div>
        </span>
    </div>
</div>
</template>


