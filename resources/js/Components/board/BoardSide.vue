<template>
    <div class="relative py-5 transition-all bg-white board-side">
        <div @click="$emit('toggle-expanded')" class="absolute flex items-center justify-center w-6 h-6 text-white border rounded-full cursor-pointer bg-purple-400/60 border-slate-400 top-20 -right-4">
            <i class="fa" :class="arrowIcon" />
        </div>

        <div v-if="isExpanded">
            <WorkspaceSelector
                v-if="false"
                :current-workspace="pageProps.user.current_workspace"
                :workspaces="pageProps.user.all_workspaces"
            />
            <SearchBar v-model="search" class="mt-5" v-if="!isHeaderMenu" />

            <div class="mt-2" :class="{'mt-12': isHeaderMenu}">
                <template v-if="!isHeaderMenu">
                    <board-side-item
                        @option="handleCommand"
                        :is-active="isPath(board.link)"
                        :board="board"
                        :key="board.link"
                        v-for="board in filteredBoards"
                    />
                </template>
                <template v-else>
                    <board-side-item-link
                        v-for="(section, index) in isHeaderMenu.side"
                        :section="section"
                        :is-active="isPath(section.to)"
                        :key="`${section.to}-${index}`"
                    />
                </template>

            </div>
            <div
                class="flex mx-5 mt-2 overflow-hidden text-gray-500 rounded"
                v-if="!showAdd && !isHeaderMenu"
            >
                <button
                    class="flex items-center justify-center w-full h-10 px-2 text-purple-400 border-2 border-purple-400 hover:bg-purple-400 hover:text-white"
                    @click="openBoardForm()"
                >
                    <i class="fa fa-plus"></i>
                </button>
            </div>
            <div
                class="flex mx-5 mt-2 overflow-hidden text-gray-500 rounded"
                v-if="showAdd && !isHeaderMenu"
            >
                <input
                    type="text"
                    class="w-full p-2 form-control"
                    placeholder="Add board name"
                    v-model="boardName"
                    ref="input"
                    @keydown.enter="addBoard()"
                    @blur="showAdd = false"
                />
                <button
                    class="p-2 text-white bg-purple-400"
                    @click.native="addBoard()"
                >
                    Add
                </button>
            </div>
        </div>
        <BoardFormModal
            :record-data="state.boardData"
            :is-open="state.isBoardFormOpen"
            @cancel="state.isBoardFormOpen=false"
            @saved="addBoard"
        />
    </div>
</template>

<script setup>
import BoardSideItem from "./BoardSideITem.vue";
import BoardSideItemLink from "./BoardSideITemLink.vue";
import BoardFormModal from "./BoardForm.vue"
import SearchBar from "../SearchBar.vue";
import WorkspaceSelector from "../workspace/WorkspaceSelector.vue";
import { computed, reactive, ref, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/inertia-vue3";


const pageProps = usePage().props;

const props = defineProps({
    boards: {
        type: Array,
        default() {
            return [];
        }
    },
    headerMenu: {
        type: Array,
        default() {
            return [];
        }
    },
    isExpanded: {
        type: Boolean,
        default: true
    }
});

const state = reactive({
    boardName: "",
    showAdd: "",
    search: "",
    isBoardFormOpen: false,
    boardData: null
});

const isHeaderMenu = computed(() => {
    return props.headerMenu.find( menuItem => isPath(menuItem.to, true));
})

const filteredBoards = computed(() => {
    return props.boards.filter(board => board.name.toLowerCase().includes(state.search.toLowerCase()));
})

const arrowIcon = computed(() => {
    return props.isExpanded ?  'fa-chevron-left' : 'fa-chevron-right';
})

const sectionName = computed(() => {
    return isHeaderMenu.value ? isHeaderMenu.value.label : "My Boards";
})

function isPath(url = "", includes) {
    const link = url.replace(window.location.origin, "");
    if (includes) {
        const root = link.split("/");
        const isPath = window.location.pathname.includes(root[1]);
        return isPath
    }
    return link == window.location.pathname;
}

function openBoardForm(board = {}) {
    state.isBoardFormOpen = true;
    state.boardData = board;
}

function addBoard(board) {
    state.isBoardFormOpen = false;
    router.reload();
}

const inputRef = ref()
function showAddForm() {
    state.showAdd = true;
    nextTick(() => {
        input.value.focus();
    });
}

function handleCommand(board, command) {
        switch (command) {
            case 'delete':
                confirmDelete(board)
                break
            default:
            break;
        }
}

function confirmDelete(board) {
    showConfirm({
        title: `Delete "${board.name}" board`,
        content: "Are you sure you want to delete this board?",
        confirmationButtonText: "Yes, delete",
        confirm: () => {
            deleteBoard(board.id)
        }
    })
}

function deleteBoard(id) {
    axios({
        url: `/api/boards/${id}`,
        method: "delete"
    }).then(() => {
        const index = props.boards.findIndex(board => board.id == id);
        const currentBoard = index >= 0 ? props.boards[index] : null;

        if (currentBoard && isPath(currentBoard.link)) {
            if (index != 0) {
                const previousBoard = props.boards[index - 1];
                return router.visit(previousBoard.link)
            }
            return router.visit('dashboard')
        }
            router.reload();
    }).catch((err) => {
        console.log(err)
    });
}
</script>

<style lang="scss">
.board-side {
    border-radius: 18px 0 0 0;
    height: 100%;
}

.board-item {
    @apply my-2 border-l-4 border-white;

    &:visited {
        @apply text-gray-600;
    }

    &.active {
        @apply border-purple-400 bg-purple-50;
    }

    &__avatar {
        @apply bg-purple-400 flex justify-center items-center;
        width: 30px;
        min-width: 30px;
        font-size: 20px;
        height: 30px;
        border-radius: 8px;
        font-weight: bolder;
        margin-right: 4px;
        color: white;
    }
}
</style>
