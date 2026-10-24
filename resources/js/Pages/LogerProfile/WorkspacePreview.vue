<script setup lang="ts">
import {
    computed,
    nextTick,
    ref,
    onMounted,
    onBeforeUnmount,
    watch,
} from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Components/templates/AppLayout.vue";
import ProfilesPillarNav from "@/Components/templates/ProfilesPillarNav.vue";
import TaskProfileLinks from "@/Components/TaskProfileLinks.vue";
import NotebookLiveEditor from "@/Components/NotebookLiveEditor.vue";
import NotebookReading from "@/Components/NotebookReading.vue";
import Modal from "@/Components/atoms/Modal.vue";
import Glyph from "@/domains/logerProfile/ProfileGlyph.vue";
import {
    pagesOf,
    type ProfileBoard,
    type ProfilePage,
    type ProfileStage,
    type BoardView,
} from "@/domains/logerProfile/profilePreview";

type Workspace = {
    boards: ProfileBoard[];
    tasks: ProfilePage[];
    purchases: { id: number; name: string; detail: string; done: boolean }[];
    occurrences: {
        id: number;
        name: string;
        detail: string;
        checked: boolean;
    }[];
    categories: {
        id: number;
        name: string;
        assigned: number;
        available: number;
    }[];
    transactions: {
        id: number;
        name: string;
        amount: number;
        currency: string;
        category: string;
    }[];
    categoryGroup: string;
    month: string;
    connections: { finance: boolean; shopping: boolean; tracking: boolean };
    availableLinks: {
        key: string;
        id: number;
        type: string;
        name: string;
        label: string;
        linked: boolean;
    }[];
};
const props = defineProps<{
    profile: { id: number; name: string };
    workspace: Workspace;
}>();
const state = ref<Workspace>(JSON.parse(JSON.stringify(props.workspace)));
const savedWorkspace = ref<Workspace>(
    JSON.parse(JSON.stringify(props.workspace)),
);
const boards = computed(() => state.value.boards);
const categories = computed(() => state.value.categories);
const purchases = computed(() => state.value.purchases);
const occurrences = computed(() => state.value.occurrences);
const connections = computed(() => state.value.connections);
const allTasks = computed(() => [
    ...boards.value.flatMap(pagesOf).filter((page) => page.kind === "task"),
    ...state.value.tasks,
]);
const pending = computed(() => allTasks.value.filter((page) => !page.done));
const upcoming = computed(() =>
    boards.value.flatMap((board) =>
        pagesOf(board)
            .filter((page) => page.kind === "event")
            .map((page) => ({ ...page, board })),
    ),
);
const tab = ref("overview");
const tabs = [
    { id: "overview", name: "Resumen" },
    { id: "boards", name: "Carpetas" },
    { id: "finance", name: "Finanzas" },
    { id: "shopping", name: "Compras" },
    { id: "tracking", name: "Seguimientos" },
];
const activeBoardId = ref<string | null>(null);
const boardHeader = ref<HTMLElement | null>(null);
const activeBoard = computed(() =>
    boards.value.find((board) => board.id === activeBoardId.value),
);
const view = ref<BoardView>("notebook");
const stageId = ref("");
const pageId = ref("");
const selectedStage = computed(() =>
    activeBoard.value?.stages.find((stage) => stage.id === stageId.value),
);
const selectedPage = computed(() =>
    selectedStage.value?.items.find((page) => page.id === pageId.value),
);
const pageStats = computed(() => {
    const text = selectedPage.value?.body ?? "";
    return {
        words: text.trim() ? text.trim().split(/\s+/u).length : 0,
        characters: Array.from(text).length,
    };
});
const hasUnsavedPage = computed(() => {
    const saved = savedWorkspace.value.boards
        .flatMap(pagesOf)
        .find((page) => page.id === pageId.value);
    return (
        !!selectedPage.value &&
        JSON.stringify(selectedPage.value) !== JSON.stringify(saved)
    );
});
const editingPage = ref(false);
const indexVisible = ref(true);
const focusMode = ref(false);
const toolsVisible = ref(false);
const statisticsVisible = ref(false);
const toggleReading = async () => {
    if (editingPage.value) await finishEditing();
    else editingPage.value = true;
};
const pageSearch = ref("");
const visiblePages = computed(
    () =>
        selectedStage.value?.items.filter((page) =>
            `${page.title} ${page.body}`
                .toLocaleLowerCase()
                .includes(pageSearch.value.toLocaleLowerCase()),
        ) ?? [],
);
const selectPage = async (id: string) => {
    if (saving.value) return;
    if (hasUnsavedPage.value && selectedPage.value) {
        await savePage(selectedPage.value);
    }
    pageId.value = id;
    editingPage.value = false;
};
const finishEditing = async () => {
    if (!selectedPage.value) return;
    await savePage(selectedPage.value);
    if (!error.value) editingPage.value = false;
};
const viewNames: Record<BoardView, string> = {
    notebook: "Cuaderno",
    kanban: "Tarjetas",
    list: "Lista",
};
const kindNames = { note: "Página", task: "Tarea", event: "Evento" };
const panel = ref<"link" | "create" | "notebook" | null>(null);
const boardName = ref("");
const notebookName = ref("");
const boardView = ref<BoardView>("notebook");
const saving = ref(false);
const error = ref("");
const saveStatus = ref("Todos los cambios guardados");
const selectedLinks = ref<string[]>([]);
const linkSearch = ref("");
const filteredLinks = computed(() =>
    state.value.availableLinks.filter((link) =>
        link.name
            .toLocaleLowerCase()
            .includes(linkSearch.value.toLocaleLowerCase()),
    ),
);
const money = (amount: number, currency = "DOP") =>
    new Intl.NumberFormat("es-DO", { style: "currency", currency }).format(
        amount,
    );
const monthLabel = computed(() =>
    new Date(state.value.month + "-01T12:00:00").toLocaleDateString("es", {
        month: "long",
        year: "numeric",
    }),
);
const displayWhen = (value?: string) =>
    value
        ? new Date(value).toLocaleString("es-DO", {
              dateStyle: "medium",
              timeStyle: "short",
          })
        : "Sin fecha";
const persist = async (method: string, path: string, data: unknown) => {
    if (saving.value) return null;
    saving.value = true;
    error.value = "";
    saveStatus.value = "Guardando…";
    try {
        const response = await axios.request({
            method,
            url:
                route("loger-profiles.preview", {
                    profileId: props.profile.id,
                }).replace("/preview", "/workspace") + path,
            data,
        });
        state.value = response.data.workspace;
        savedWorkspace.value = JSON.parse(
            JSON.stringify(response.data.workspace),
        );
        saveStatus.value = "Guardado";
        return response.data;
    } catch (failure: any) {
        error.value =
            Object.values(failure.response?.data?.errors ?? {})
                .flat()
                .join(" ") ||
            "No se pudo guardar. Revisa la conexión e inténtalo otra vez.";
        saveStatus.value = "Cambios sin guardar";
        return null;
    } finally {
        saving.value = false;
    }
};
const openLinks = () => {
    selectedLinks.value = state.value.availableLinks
        .filter((link) => link.linked)
        .map((link) => link.key);
    panel.value = "link";
};
const saveLinks = async () => {
    const selections = state.value.availableLinks
        .filter((link) => selectedLinks.value.includes(link.key))
        .map(({ type, id }) => ({ type, id }));
    if (await persist("put", "/links", { selections })) panel.value = null;
};
const savePage = async (page: ProfilePage) => {
    await persist("patch", `/items/${page.id}`, {
        title: page.title,
        body: page.body,
        kind: page.kind,
        when: page.when || null,
        done: page.done,
    });
};
const toggleTask = async (page: ProfilePage) => {
    if (!(await persist("patch", `/items/${page.id}`, { done: page.done })))
        page.done = !page.done;
};
const togglePurchase = async (item: Workspace["purchases"][number]) => {
    if (!(await persist("patch", `/items/${item.id}`, { done: item.done })))
        item.done = !item.done;
};
const registerOccurrence = async (item: Workspace["occurrences"][number]) => {
    await persist("patch", `/occurrences/${item.id}`, {
        checked: !item.checked,
    });
};
const changeView = async (key: BoardView) => {
    if (!activeBoard.value) return;
    if (hasUnsavedPage.value && selectedPage.value) {
        await savePage(selectedPage.value);
    }
    if (
        await persist("patch", `/boards/${activeBoard.value.id}`, { view: key })
    )
        view.value = key;
};
const navigateViews = async (event: KeyboardEvent, key: BoardView) => {
    const keys: BoardView[] = ["notebook", "kanban", "list"];
    if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) return;
    event.preventDefault();
    const index = keys.indexOf(key);
    const target =
        event.key === "Home"
            ? 0
            : event.key === "End"
              ? 2
              : (index + (event.key === "ArrowRight" ? 1 : 2)) % 3;
    const group = (event.currentTarget as HTMLElement).parentElement;
    await changeView(keys[target]);
    (group?.querySelectorAll('[role="radio"]')[target] as HTMLElement)?.focus();
};
const openBoard = (board: ProfileBoard) => {
    activeBoardId.value = board.id;
    view.value = board.view;
    stageId.value = "";
    pageId.value = "";
    void nextTick(() => boardHeader.value?.scrollIntoView({ block: "start" }));
};
const saveNotebook = async (notebook: ProfileStage) => {
    if (!activeBoard.value || !notebook.name.trim()) return;
    await persist(
        "patch",
        `/boards/${activeBoard.value.id}/notebooks/${notebook.id}`,
        {
            name: notebook.name.trim(),
        },
    );
};
const openNotebook = (notebook: ProfileStage) => {
    editingPage.value = false;
    pageSearch.value = "";
    stageId.value = notebook.id;
    pageId.value = notebook.items[0]?.id ?? "";
    view.value = "notebook";
    void nextTick(() => boardHeader.value?.scrollIntoView({ block: "start" }));
};
const backFromNotebook = () => {
    if (selectedStage.value) {
        stageId.value = "";
        pageId.value = "";
    } else {
        activeBoardId.value = null;
    }
};
const createNotebook = async () => {
    if (!activeBoard.value || !notebookName.value.trim()) return;
    const response = await persist(
        "post",
        `/boards/${activeBoard.value.id}/notebooks`,
        { name: notebookName.value.trim() },
    );
    if (response) {
        panel.value = null;
        notebookName.value = "";
        const notebook = activeBoard.value?.stages.find(
            (stage) => stage.id === response.notebook_id,
        );
        if (notebook) openNotebook(notebook);
    }
};
const openPage = (board: ProfileBoard, page: ProfilePage) => {
    openBoard(board);
    stageId.value =
        board.stages.find((stage) =>
            stage.items.some((item) => item.id === page.id),
        )?.id ?? "";
    pageId.value = page.id;
    editingPage.value = false;
    view.value = "notebook";
};
const newPage = async () => {
    if (!activeBoard.value || !selectedStage.value) return;
    const response = await persist(
        "post",
        `/boards/${activeBoard.value.id}/pages`,
        { stage_id: Number(selectedStage.value.id), title: "" },
    );
    if (response) {
        pageId.value = response.item_id;
        editingPage.value = true;
        view.value = "notebook";
    }
};
const createBoard = async () => {
    if (!boardName.value.trim()) return;
    const response = await persist("post", "/boards", {
        name: boardName.value.trim(),
        view: boardView.value,
    });
    if (response) {
        panel.value = null;
        boardName.value = "";
        const board = boards.value.find(
            (board) => board.id === response.board_id,
        );
        if (board) openBoard(board);
    }
};
const followPageLink = (title: string) => {
    const page = selectedStage.value?.items.find(
        (item) => item.title.toLocaleLowerCase() === title.toLocaleLowerCase(),
    );
    if (page) selectPage(page.id);
};
const menuOpen = ref(false);
let autoSaveTimer: ReturnType<typeof setTimeout> | null = null;
watch(
    () =>
        selectedPage.value && editingPage.value
            ? JSON.stringify(selectedPage.value)
            : null,
    () => {
        if (!hasUnsavedPage.value || !editingPage.value) return;
        if (autoSaveTimer) clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            if (selectedPage.value && hasUnsavedPage.value && !saving.value) {
                savePage(selectedPage.value);
            }
        }, 1500);
    },
);
const saveShortcut = (event: KeyboardEvent) => {
    if (
        (event.ctrlKey || event.metaKey) &&
        event.key.toLowerCase() === "s" &&
        selectedPage.value &&
        editingPage.value
    ) {
        event.preventDefault();
        if (!saving.value) void finishEditing();
    }
};
onMounted(() => window.addEventListener("keydown", saveShortcut));
onBeforeUnmount(() => window.removeEventListener("keydown", saveShortcut));
onMounted(() => {
    if (window.matchMedia?.("(max-width: 1023px)").matches)
        indexVisible.value = false;
    const query = new URLSearchParams(window.location.search);
    const board = boards.value.find((item) => item.id === query.get("board"));
    if (!board) return;
    activeBoardId.value = board.id;
    const notebook = board.stages.find(
        (item) => item.id === query.get("notebook"),
    );
    stageId.value = notebook?.id ?? "";
    pageId.value =
        notebook?.items.find((item) => item.id === query.get("page"))?.id ??
        notebook?.items[0]?.id ??
        "";
    const requestedView = query.get("view");
    view.value =
        requestedView === "kanban" || requestedView === "list"
            ? requestedView
            : "notebook";
});
watch(
    [activeBoardId, stageId, pageId, view],
    () => {
        if (typeof window === "undefined") return;
        const url = new URL(window.location.href);
        for (const [key, value] of Object.entries({
            board: activeBoardId.value,
            notebook: stageId.value,
            page: pageId.value,
            view: activeBoardId.value ? view.value : "",
        })) {
            if (value) url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        }
        window.history.replaceState(window.history.state, "", url);
    },
    { flush: "post" },
);
const setTab = (id: string) => {
    tab.value = id;
    activeBoardId.value = null;
};
</script>

<template>
    <AppLayout
        :title="profile.name"
        :focus-mode="focusMode && !!selectedStage"
        :hide-tools="!!selectedStage && !toolsVisible"
        :show-back-button="true"
        @back="router.visit('/loger-profiles')"
    >
        <template #header><ProfilesPillarNav /></template>
        <main
            :class="focusMode && selectedStage ? '!mt-0' : ''"
            class="profile-workspace mx-auto mt-12 w-full max-w-[1600px] px-4 pb-8 pt-3 text-body-1 sm:px-6 lg:px-8"
        >
            <p
                v-if="!selectedStage"
                role="status"
                class="mb-3 text-right text-xs text-body-1/70"
            >
                {{ saveStatus }}
            </p>
            <p
                v-if="error"
                role="alert"
                class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            >
                {{ error }}
            </p>
            <div
                v-if="activeBoard && !selectedStage"
                class="mb-1 flex items-center gap-2 text-sm text-body-1/75"
            >
                <button
                    class="min-h-[44px] hover:text-primary"
                    @click="activeBoardId = null"
                >
                    {{ profile.name }}</button
                ><span>/ {{ activeBoard.name }}</span>
            </div>
            <header
                v-if="!activeBoard"
                class="flex flex-wrap items-center justify-between gap-5 pb-6"
            >
                <div class="flex min-w-0 items-center gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-primary/25 bg-primary/10 text-2xl font-semibold text-primary"
                    >
                        {{ profile.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <button
                            class="mb-1 text-xs text-body-1/75 hover:text-primary"
                            @click="router.visit('/loger-profiles')"
                        >
                            Perfiles /
                        </button>
                        <h1
                            class="break-words text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            {{ profile.name }}
                        </h1>
                        <p class="mt-1 text-sm text-body-1/75">
                            Su día a día, sus historias y todo lo que los
                            conecta.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button class="workspace-button" @click="openLinks">
                        <Glyph name="link" />Vincular</button
                    ><button
                        class="workspace-button workspace-primary"
                        @click="panel = 'create'"
                    >
                        <Glyph name="plus" />Nueva carpeta
                    </button>
                </div>
            </header>
            <nav
                v-if="!selectedStage"
                aria-label="Secciones del perfil"
                class="profile-tabs mb-4 flex gap-4 overflow-x-auto overflow-y-hidden border-b border-body-1/10"
            >
                <button
                    v-for="item in tabs"
                    :key="item.id"
                    class="-mb-px flex shrink-0 items-center gap-2 border-b-2 pb-3 text-sm transition-colors"
                    :class="
                        tab === item.id && !activeBoard
                            ? 'border-primary font-semibold text-primary'
                            : 'border-transparent text-body-1/75 hover:text-body-1'
                    "
                    :aria-current="
                        tab === item.id && !activeBoard ? 'page' : undefined
                    "
                    @click="setTab(item.id)"
                >
                    {{ item.name
                    }}<span
                        v-if="item.id === 'boards'"
                        class="rounded bg-body-1/5 px-1.5 py-0.5 text-xs"
                        >{{ boards.length }}</span
                    >
                </button>
            </nav>

            <template v-if="activeBoard">
                <div
                    ref="boardHeader"
                    class="mb-2 flex min-h-[44px] scroll-mt-32 flex-wrap items-center justify-between gap-2"
                >
                    <div
                        v-if="selectedStage"
                        class="flex min-w-0 items-center gap-2 text-sm"
                    >
                        <button
                            :aria-label="'Volver a ' + activeBoard.name"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-body-1/75 hover:bg-body-1/5"
                            @click="backFromNotebook"
                        >
                            ←
                        </button>
                        <button
                            class="truncate text-body-1/75 hover:text-primary"
                            @click="activeBoardId = null"
                        >
                            {{ profile.name }}
                        </button>
                        <span class="text-body-1/40">/</span>
                        <button
                            class="truncate text-body-1/75 hover:text-primary"
                            @click="backFromNotebook"
                        >
                            {{ activeBoard.name }}
                        </button>
                        <span class="text-body-1/40">/</span>
                        <h2 class="truncate text-sm font-semibold">
                            {{ selectedStage.name }}
                        </h2>
                    </div>
                    <div v-else>
                        <button
                            class="mb-3 flex items-center gap-1 text-xs text-body-1/75 hover:text-primary"
                            @click="backFromNotebook"
                        >
                            {{
                                selectedStage
                                    ? "← Volver a " + activeBoard.name
                                    : "← Volver al perfil"
                            }}
                        </button>
                        <h2
                            class="flex items-center gap-3 text-2xl font-semibold"
                        >
                            <Glyph
                                :name="selectedStage ? 'book' : 'folder'"
                                :style="{ color: activeBoard.color }"
                            />{{ selectedStage?.name ?? activeBoard.name }}
                        </h2>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <div
                            class="flex rounded-lg border border-body-1/10 bg-base-lvl-3 p-1"
                            v-if="selectedStage"
                            role="radiogroup"
                            aria-label="Vista del cuaderno"
                        >
                            <button
                                v-for="(label, key) in viewNames"
                                :key="key"
                                class="rounded-md px-3 py-1.5 text-xs"
                                :class="
                                    view === key
                                        ? 'bg-body-1/10 font-semibold text-body-1'
                                        : 'text-body-1/70'
                                "
                                role="radio"
                                :aria-checked="view === key"
                                :tabindex="view === key ? 0 : -1"
                                @keydown="navigateViews($event, key)"
                                @click="changeView(key)"
                            >
                                {{ label }}
                            </button>
                        </div>
                        <button
                            v-if="selectedStage"
                            class="workspace-button"
                            :disabled="saving"
                            @click="newPage"
                        >
                            <Glyph name="plus" />Nueva página
                        </button>
                        <button
                            v-else
                            class="workspace-button workspace-primary"
                            @click="panel = 'notebook'"
                        >
                            <Glyph name="plus" />Nuevo cuaderno
                        </button>
                    </div>
                </div>
                <section
                    v-if="!selectedStage"
                    aria-label="Cuadernos de la carpeta"
                >
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold">Cuadernos</h3>
                            <p class="mt-1 text-sm text-body-1/75">
                                Cada cuaderno reúne sus propias páginas.
                            </p>
                        </div>
                        <span class="text-xs text-body-1/70"
                            >{{ activeBoard.stages.length }} {{ activeBoard.stages.length === 1 ? 'cuaderno' : 'cuadernos' }}</span
                        >
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <button
                            v-for="notebook in activeBoard.stages"
                            :key="notebook.id"
                            class="group flex min-h-[180px] flex-col items-start gap-4 rounded-r-xl rounded-l-md border border-body-1/10 border-l-[6px] bg-base-lvl-3 p-5 text-left transition hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :style="{ borderLeftColor: activeBoard.color }"
                            @click="openNotebook(notebook)"
                        >
                            <Glyph
                                name="book"
                                :style="{ color: activeBoard.color }"
                            />
                            <h4 class="break-words text-base font-semibold">
                                {{ notebook.name }}
                            </h4>
                            <div
                                class="mt-auto flex w-full items-center justify-between gap-2 text-xs text-body-1/75"
                            >
                                <span
                                    >{{ notebook.items.length }}
                                    {{
                                        notebook.items.length === 1
                                            ? "página"
                                            : "páginas"
                                    }}</span
                                ><Glyph name="arrow" class="!h-4 !w-4" />
                            </div>
                        </button>
                        <button
                            class="flex min-h-[180px] flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-body-1/20 p-5 text-sm text-body-1/75 hover:border-primary hover:text-primary"
                            @click="panel = 'notebook'"
                        >
                            <Glyph name="plus" />Nuevo cuaderno
                        </button>
                    </div>
                </section>
                <section
                    v-else-if="view === 'notebook'"
                    :style="
                        focusMode
                            ? { minHeight: 'calc(100dvh - 72px)' }
                            : undefined
                    "
                    class="grid min-h-[calc(100dvh-190px)] overflow-hidden rounded-xl border border-body-1/10 bg-base-lvl-3"
                    :class="
                        indexVisible
                            ? 'grid-rows-[auto_auto_1fr_auto] lg:grid-cols-[230px_minmax(0,1fr)] lg:grid-rows-[auto_1fr_auto]'
                            : 'grid-cols-1 grid-rows-[auto_1fr_auto]'
                    "
                >
                    <div
                        class="flex min-h-[48px] items-center justify-between gap-2 border-b border-body-1/10 px-2 lg:col-span-full"
                    >
                        <button
                            class="flex h-11 w-11 items-center justify-center rounded-md text-body-1/70 hover:bg-body-1/5 focus-visible:ring-2 focus-visible:ring-primary"
                            :aria-expanded="indexVisible"
                            aria-controls="notebook-index"
                            :aria-label="
                                indexVisible
                                    ? 'Ocultar índice'
                                    : 'Mostrar índice'
                            "
                            :title="
                                indexVisible
                                    ? 'Ocultar índice'
                                    : 'Mostrar índice'
                            "
                            @click="indexVisible = !indexVisible"
                        >
                            <Glyph name="sidebar" />
                        </button>
                        <div class="flex items-center gap-1">
                            <button
                                class="flex h-11 w-11 items-center justify-center rounded-md text-body-1/80 hover:bg-body-1/5 focus-visible:ring-2 focus-visible:ring-primary"
                                :aria-pressed="focusMode"
                                :aria-label="
                                    focusMode
                                        ? 'Salir de concentración'
                                        : 'Modo concentración'
                                "
                                :title="
                                    focusMode
                                        ? 'Salir de concentración'
                                        : 'Modo concentración'
                                "
                                @click="focusMode = !focusMode"
                            >
                                <Glyph
                                    :name="focusMode ? 'collapse' : 'expand'"
                                />
                            </button>
                            <button
                                v-if="selectedPage"
                                class="flex h-11 w-11 items-center justify-center rounded-md text-body-1/70 hover:bg-body-1/5 focus-visible:ring-2 focus-visible:ring-primary"
                                :class="
                                    editingPage
                                        ? 'bg-primary/15 text-primary ring-1 ring-primary/40'
                                        : ''
                                "
                                :aria-pressed="editingPage"
                                :disabled="saving"
                                :aria-label="
                                    editingPage
                                        ? 'Guardar y leer'
                                        : 'Editar página'
                                "
                                :title="
                                    editingPage
                                        ? 'Guardar y leer'
                                        : 'Editar página'
                                "
                                @click="toggleReading"
                            >
                                <Glyph
                                    :name="editingPage ? 'book' : 'pencil'"
                                />
                            </button>
                            <div class="relative">
                                <button
                                    aria-label="Opciones de página"
                                    title="Opciones de página"
                                    class="flex h-11 w-11 items-center justify-center rounded-md text-body-1/70 hover:bg-body-1/5 focus-visible:ring-2 focus-visible:ring-primary"
                                    @click="menuOpen = !menuOpen"
                                >
                                    <Glyph name="more" />
                                </button>
                                <Teleport to="body">
                                    <div
                                        v-if="menuOpen"
                                        class="fixed inset-0 z-10"
                                        @click="menuOpen = false"
                                    />
                                </Teleport>
                                <div
                                    v-if="menuOpen"
                                    class="absolute right-0 top-full z-20 flex w-60 flex-col gap-2 rounded-lg border border-body-1/15 bg-base-lvl-3 p-3 shadow-xl"
                                    @keydown.esc="menuOpen = false"
                                >
                                    <button
                                        class="min-h-[44px] rounded px-2 text-left text-sm hover:bg-body-1/5"
                                        :disabled="saving"
                                        @click="menuOpen = false; newPage()"
                                    >
                                        Nueva página
                                    </button>
                                    <label
                                        class="flex min-h-[44px] items-center gap-2 text-sm"
                                        ><input
                                            type="checkbox"
                                            v-model="toolsVisible"
                                        />Mostrar herramientas</label
                                    >
                                    <label
                                        class="flex min-h-[44px] items-center gap-2 text-sm"
                                        ><input
                                            type="checkbox"
                                            v-model="statisticsVisible"
                                        />Mostrar estadísticas</label
                                    >
                                    <label
                                        v-if="selectedPage"
                                        class="flex flex-col gap-2 text-xs text-body-1/70"
                                        >Tipo de página<select
                                            v-model="selectedPage.kind"
                                            aria-label="Tipo de página"
                                            :disabled="saving"
                                            @change="savePage(selectedPage)"
                                            class="workspace-input"
                                        >
                                            <option value="note">Página</option>
                                            <option value="task">Tarea</option>
                                            <option value="event">
                                                Evento
                                            </option>
                                        </select></label
                                    >
                                    <label
                                        class="flex flex-col gap-2 text-xs text-body-1/70"
                                        >Nombre del cuaderno<input
                                            v-model="selectedStage.name"
                                            aria-label="Nombre del cuaderno"
                                            :disabled="saving"
                                            @change="
                                                saveNotebook(selectedStage)
                                            "
                                            class="workspace-input"
                                    /></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <aside
                        v-if="indexVisible"
                        id="notebook-index"
                        class="border-b border-body-1/10 bg-base-lvl-2/40 p-4 lg:border-b-0 lg:border-r"
                    >
                        <input
                            v-model="pageSearch"
                            type="search"
                            aria-label="Buscar en el cuaderno"
                            placeholder="Buscar en el cuaderno…"
                            class="workspace-input my-3 w-full"
                        />
                        <div
                            v-for="stage in [selectedStage]"
                            :key="stage.id"
                            class="mb-5"
                        >
                            <div class="flex flex-col gap-1">
                                <button
                                    v-for="page in visiblePages"
                                    :key="page.id"
                                    class="flex min-h-[44px] items-start gap-2 rounded-md px-2 py-3 text-left text-sm leading-5"
                                    :aria-current="
                                        pageId === page.id ? 'page' : undefined
                                    "
                                    :disabled="saving"
                                    :class="
                                        pageId === page.id
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-body-1/75 hover:bg-body-1/5'
                                    "
                                    @click="selectPage(page.id)"
                                >
                                    <Glyph
                                        :name="
                                            page.kind === 'task'
                                                ? 'check'
                                                : page.kind === 'event'
                                                  ? 'calendar'
                                                  : 'book'
                                        "
                                        class="!h-4 !w-4"
                                    /><span>{{ page.title || 'Sin título' }}</span>
                                </button>
                            </div>
                        </div>
                        <p
                            v-if="!visiblePages.length"
                            class="py-3 text-sm text-body-1/75"
                        >
                            No hay páginas que coincidan.
                        </p>
                    </aside>
                    <article
                        v-if="selectedPage"
                        class="min-w-0 px-4 py-5 sm:px-6 lg:px-8"
                    >
                        <h3
                            v-if="!editingPage"
                            class="break-words text-2xl font-semibold leading-tight sm:text-3xl"
                        >
                            {{ selectedPage.title || 'Sin título' }}
                        </h3>
                        <input
                            v-else
                            v-model="selectedPage.title"
                            aria-label="Título de la página"
                            placeholder="Sin título"
                            :disabled="saving"
                            class="w-full border-0 bg-transparent p-0 text-2xl font-semibold leading-tight placeholder:text-body-1/40 focus:ring-0 sm:text-3xl"
                        />
                        <label
                            v-if="selectedPage.kind === 'task'"
                            class="mt-5 flex items-center gap-2 text-sm"
                            ><input
                                v-model="selectedPage.done"
                                @change="toggleTask(selectedPage)"
                                :disabled="saving"
                                type="checkbox"
                                class="rounded text-primary focus:ring-primary"
                            />{{
                                selectedPage.done
                                    ? "Completada"
                                    : "Marcar como completada"
                            }}</label
                        >
                        <p
                            v-if="selectedPage.kind === 'event' && !editingPage"
                            class="mt-5 text-sm text-body-1/85"
                        >
                            <span class="mr-2 font-medium"
                                >Fecha del evento</span
                            >
                            <time
                                v-if="selectedPage.when"
                                :datetime="selectedPage.when"
                                >{{ displayWhen(selectedPage.when) }}</time
                            >
                            <span v-else>Sin fecha programada</span>
                        </p>
                        <label
                            v-if="selectedPage.kind === 'event' && editingPage"
                            class="mt-5 flex flex-col gap-2 text-sm text-body-1/75"
                            >Cuándo<input
                                v-model="selectedPage.when"
                                type="datetime-local"
                                :disabled="saving"
                                placeholder="Añadir fecha y hora"
                                class="workspace-input"
                        /></label>
                        <TaskProfileLinks
                            v-if="selectedPage.kind === 'task'"
                            :task-id="selectedPage.id"
                        />
                        <NotebookReading
                            v-if="!editingPage && selectedPage.body.trim()"
                            :content="selectedPage.body"
                            :pages="selectedStage?.items ?? []"
                            @open-page="followPageLink"
                            class="mt-8"
                        />
                        <p v-else-if="!editingPage" class="mt-8 text-body-1/75">
                            Esta página aún no tiene contenido. Pulsa Editar
                            para empezar.
                        </p>
                        <NotebookLiveEditor
                            v-if="editingPage"
                            :key="selectedPage.id"
                            v-model="selectedPage.body"
                            :disabled="saving"
                            :pages="selectedStage?.items ?? []"
                        />
                        <button
                            v-if="editingPage"
                            class="workspace-button workspace-primary mt-4"
                            :disabled="saving"
                            @click="finishEditing"
                        >
                            Guardar página
                        </button>
                    </article>
                    <div
                        v-else
                        class="flex flex-col items-center justify-center gap-4 p-10 text-center"
                    >
                        <Glyph class="!h-8 !w-8 text-body-1/30" />
                        <p class="text-sm text-body-1/75">
                            Tu primera página empieza aquí.
                        </p>
                        <button class="workspace-button" @click="newPage">
                            Nueva página
                        </button>
                    </div>
                    <footer
                        v-if="selectedPage"
                        aria-label="Estado del documento"
                        class="flex h-6 items-center justify-end gap-4 overflow-x-auto whitespace-nowrap border-t border-body-1/10 px-3 text-[11px] leading-none text-body-1/70 lg:col-span-full"
                    >
                        <span role="status" aria-live="polite">{{
                            saving
                                ? "Guardando…"
                                : hasUnsavedPage
                                  ? "Cambios sin guardar"
                                  : "Guardado"
                        }}</span>
                        <div class="flex flex-wrap items-center gap-3">
                            <span>{{
                                editingPage ? "Edición" : "Lectura"
                            }}</span
                            ><span v-if="statisticsVisible"
                                >{{ pageStats.words }} palabras</span
                            ><span v-if="statisticsVisible"
                                >{{ pageStats.characters }} caracteres</span
                            >
                        </div>
                    </footer>
                </section>
                <section
                    v-else-if="view === 'kanban'"
                    class="grid items-start gap-4 md:grid-cols-3"
                >
                    <div
                        v-for="stage in [selectedStage]"
                        :key="stage.id"
                        class="rounded-xl bg-base-lvl-2/40 p-3"
                    >
                        <h3
                            class="mb-3 flex items-center justify-between px-1 text-sm font-semibold"
                        >
                            {{ stage.name
                            }}<span
                                class="text-xs font-normal text-body-1/40"
                                >{{ stage.items.length }}</span
                            >
                        </h3>
                        <div class="flex flex-col gap-3">
                            <button
                                v-for="page in stage.items"
                                :key="page.id"
                                class="rounded-lg border border-body-1/10 bg-base-lvl-3 p-4 text-left transition hover:border-primary/40"
                                @click="openPage(activeBoard, page)"
                            >
                                <span class="text-xs text-body-1/70">{{
                                    kindNames[page.kind]
                                }}</span>
                                <h4
                                    class="mt-2 text-sm font-medium"
                                    :class="
                                        page.done
                                            ? 'line-through opacity-50'
                                            : ''
                                    "
                                >
                                    {{ page.title }}
                                </h4>
                                <p
                                    v-if="page.when"
                                    class="mt-4 text-xs text-body-1/70"
                                >
                                    {{ displayWhen(page.when) }}
                                </p>
                            </button>
                        </div>
                        <button
                            class="mt-3 w-full rounded-md py-2 text-left text-xs text-body-1/70 hover:text-primary"
                            @click="
                                stageId = stage.id;
                                newPage();
                            "
                        >
                            + Nueva página
                        </button>
                    </div>
                </section>
                <section
                    v-else
                    class="overflow-hidden rounded-xl border border-body-1/10 bg-base-lvl-3"
                >
                    <template v-for="stage in [selectedStage]" :key="stage.id"
                        ><h3
                            class="bg-base-lvl-2/40 px-5 py-3 text-xs font-semibold text-body-1/75"
                        >
                            {{ stage.name }}
                        </h3>
                        <button
                            v-for="page in stage.items"
                            :key="page.id"
                            class="flex w-full items-center gap-4 border-t border-body-1/5 px-5 py-4 text-left hover:bg-body-1/5"
                            @click="openPage(activeBoard, page)"
                        >
                            <Glyph
                                :name="
                                    page.kind === 'task'
                                        ? 'check'
                                        : page.kind === 'event'
                                          ? 'calendar'
                                          : 'book'
                                "
                                class="text-body-1/40"
                            /><span class="flex-1 text-sm">{{
                                page.title
                            }}</span
                            ><span class="text-xs text-body-1/40">{{
                                kindNames[page.kind]
                            }}</span
                            ><Glyph
                                name="arrow"
                                class="!h-4 !w-4 text-body-1/40"
                            /></button
                    ></template>
                </section>
            </template>

            <div
                v-else
                class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_210px]"
            >
                <div class="flex min-w-0 flex-col">
                    <section
                        v-if="tab === 'overview'"
                        class="mb-8 grid gap-6 border-b border-body-1/10 pb-7 lg:grid-cols-2"
                    >
                        <div>
                            <div class="mb-4 flex items-center gap-2">
                                <Glyph name="calendar" class="text-primary" />
                                <h2 class="text-base font-semibold">
                                    Lo próximo
                                </h2>
                            </div>
                            <button
                                v-for="event in upcoming"
                                :key="event.id"
                                class="flex w-full items-start justify-between gap-3 border-b border-body-1/5 py-3 text-left last:border-0 hover:text-primary"
                                @click="openPage(event.board, event)"
                            >
                                <div>
                                    <p class="text-sm font-medium">
                                        {{ event.title }}
                                    </p>
                                    <p class="mt-1 text-xs text-body-1/70">
                                        {{ event.board.name }} /
                                        {{ displayWhen(event.when) }}
                                    </p>
                                </div>
                                <Glyph
                                    name="arrow"
                                    class="!h-4 !w-4 text-body-1/30"
                                />
                            </button>
                            <p class="mt-3 text-xs text-body-1/40">
                                Los eventos de sus tableros, reunidos aquí.
                            </p>
                        </div>
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-base font-semibold">
                                    Por hacer
                                </h2>
                                <span class="text-xs text-body-1/70"
                                    >{{ pending.length }} pendientes</span
                                >
                            </div>
                            <label
                                v-for="page in allTasks"
                                :key="page.id"
                                class="flex min-h-[48px] items-start gap-3 border-b border-body-1/5 py-3 text-sm last:border-0"
                                ><input
                                    v-model="page.done"
                                    @change="toggleTask(page)"
                                    :disabled="saving"
                                    type="checkbox"
                                    class="mt-0.5 rounded border-body-1/20 bg-transparent text-primary focus:ring-primary"
                                /><span
                                    :class="
                                        page.done
                                            ? 'text-body-1/35 line-through'
                                            : 'text-body-1/80'
                                    "
                                    >{{ page.title
                                    }}<a
                                        v-if="page.source_url"
                                        :href="page.source_url"
                                        class="ml-2 inline-block min-h-[24px] text-xs text-primary underline"
                                        >Abrir tarea</a
                                    ></span
                                ></label
                            >
                        </div>
                    </section>
                    <section
                        v-if="tab === 'overview' || tab === 'boards'"
                        class="order-first mb-8"
                    >
                        <div
                            class="mb-4 flex items-center justify-between gap-4"
                        >
                            <div>
                                <h2 class="text-base font-semibold">
                                    Sus carpetas
                                </h2>
                                <p class="mt-1 text-xs text-body-1/70">
                                    Un espacio para cada parte de su vida.
                                </p>
                            </div>
                            <button
                                class="text-xs font-medium text-primary"
                                @click="panel = 'create'"
                            >
                                + Nueva carpeta
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <button
                                v-for="board in boards"
                                :key="board.id"
                                class="board-cover group relative flex min-h-[170px] flex-col items-start overflow-hidden rounded-lg border border-body-1/10 bg-base-lvl-3 p-5 text-left transition hover:border-body-1/30"
                                @click="openBoard(board)"
                            >
                                <span
                                    class="absolute inset-y-0 left-0 w-1"
                                    :style="{ backgroundColor: board.color }"
                                ></span
                                ><Glyph
                                    name="folder"
                                    :style="{ color: board.color }"
                                    class="mb-5 !h-6 !w-6"
                                />
                                <h3 class="text-base font-semibold">
                                    {{ board.name }}
                                </h3>
                                <p
                                    class="mt-1 text-xs leading-5 text-body-1/70"
                                >
                                    {{ board.description }}
                                </p>
                                <div
                                    class="mt-auto flex w-full items-center justify-between gap-2 pt-5 text-xs text-body-1/70"
                                >
                                    <span
                                        >{{ board.stages.length }}
                                        {{
                                            board.stages.length === 1
                                                ? "cuaderno"
                                                : "cuadernos"
                                        }}
                                        ·
                                        {{ pagesOf(board).length }}
                                        {{ pagesOf(board).length === 1 ? 'página' : 'páginas' }}</span
                                    ><Glyph
                                        name="arrow"
                                        class="!h-4 !w-4 transition group-hover:text-primary"
                                    />
                                </div>
                            </button>
                        </div>
                    </section>
                    <div
                        class="grid gap-7"
                        :class="tab === 'overview' ? 'lg:grid-cols-2' : ''"
                    >
                        <section
                            v-if="
                                (tab === 'overview' || tab === 'finance') &&
                                connections.finance
                            "
                            class="min-w-0"
                        >
                            <h2
                                class="mb-4 flex items-center gap-2 text-base font-semibold"
                            >
                                <Glyph name="wallet" class="text-body-1/70" />Su
                                presupuesto
                            </h2>
                            <div
                                class="rounded-xl border border-body-1/10 bg-base-lvl-3 p-4"
                            >
                                <div
                                    class="mb-5 flex items-center justify-between"
                                >
                                    <span
                                        class="rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary"
                                        >{{ state.categoryGroup }}</span
                                    ><span class="text-xs text-body-1/40">{{
                                        monthLabel
                                    }}</span>
                                </div>
                                <div
                                    v-for="category in categories"
                                    :key="category.name"
                                    class="flex items-center justify-between gap-3 border-b border-body-1/5 py-3 last:border-0"
                                >
                                    <span class="text-sm">{{
                                        category.name
                                    }}</span>
                                    <div class="text-right">
                                        <p class="text-sm tabular-nums">
                                            {{ money(category.available) }}
                                        </p>
                                        <p class="text-[11px] text-body-1/40">
                                            disponibles
                                        </p>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-body-1/40">
                                    Vincular el grupo reúne sus categorías y
                                    transacciones.
                                </p>
                            </div>
                            <div v-if="tab === 'finance'" class="mt-6">
                                <h3 class="mb-3 text-sm font-semibold">
                                    Últimas transacciones vinculadas
                                </h3>
                                <p
                                    v-if="!state.transactions.length"
                                    class="text-sm text-body-1/70"
                                >
                                    No hay transacciones en las categorías
                                    vinculadas.
                                </p>
                                <div
                                    v-for="item in state.transactions"
                                    :key="item.id"
                                    class="flex justify-between gap-3 border-b border-body-1/10 py-4 text-sm"
                                >
                                    <div>
                                        <p>{{ item.name }}</p>
                                        <p class="mt-1 text-xs text-body-1/70">
                                            {{ item.category }}
                                        </p>
                                    </div>
                                    <span class="tabular-nums">{{
                                        money(item.amount, item.currency)
                                    }}</span>
                                </div>
                            </div>
                        </section>
                        <section
                            v-if="
                                (tab === 'overview' || tab === 'shopping') &&
                                connections.shopping
                            "
                        >
                            <h2
                                class="mb-4 flex items-center gap-2 text-base font-semibold"
                            >
                                <Glyph
                                    name="basket"
                                    class="text-body-1/70"
                                />Para comprar
                            </h2>
                            <div
                                class="rounded-xl border border-body-1/10 bg-base-lvl-3 p-4"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between gap-2"
                                >
                                    <span class="text-sm font-medium"
                                        >Lista de {{ profile.name }}</span
                                    ><span class="text-xs text-body-1/40"
                                        >{{
                                            purchases.filter(
                                                (item) => !item.done,
                                            ).length
                                        }}
                                        pendientes</span
                                    >
                                </div>
                                <label
                                    v-for="item in purchases"
                                    :key="item.name"
                                    class="flex items-start gap-3 border-b border-body-1/5 py-3 last:border-0"
                                    ><input
                                        v-model="item.done"
                                        @change="togglePurchase(item)"
                                        :disabled="saving"
                                        type="checkbox"
                                        class="mt-1 rounded text-primary focus:ring-primary"
                                    /><span
                                        ><span
                                            class="block text-sm"
                                            :class="
                                                item.done
                                                    ? 'text-body-1/35 line-through'
                                                    : ''
                                            "
                                            >{{ item.name }}</span
                                        ><span
                                            class="mt-1 block text-xs text-body-1/40"
                                            >{{ item.detail }}</span
                                        ></span
                                    ></label
                                >
                            </div>
                        </section>
                    </div>
                    <section v-if="tab === 'tracking' && connections.tracking">
                        <h2 class="mb-2 text-base font-semibold">
                            Sus seguimientos
                        </h2>
                        <p class="mb-5 text-sm text-body-1/70">
                            Las necesidades recurrentes que quieres tener
                            presentes.
                        </p>
                        <div
                            v-for="item in occurrences"
                            :key="item.name"
                            class="flex flex-wrap items-center justify-between gap-4 border-b border-body-1/10 py-5"
                        >
                            <div>
                                <h3 class="font-medium">{{ item.name }}</h3>
                                <p class="mt-1 text-sm text-body-1/70">
                                    {{ item.detail }}
                                </p>
                            </div>
                            <button
                                class="workspace-button"
                                :class="item.checked ? 'text-success' : ''"
                                @click="registerOccurrence(item)"
                                :disabled="saving"
                            >
                                <Glyph name="check" />{{
                                    item.checked
                                        ? "Revisado"
                                        : "Marcar revisado"
                                }}
                            </button>
                        </div>
                    </section>
                    <div
                        v-if="
                            (tab === 'finance' && !connections.finance) ||
                            (tab === 'shopping' && !connections.shopping) ||
                            (tab === 'tracking' && !connections.tracking)
                        "
                        class="rounded-xl border border-dashed border-body-1/20 p-8 text-center"
                    >
                        <p class="text-sm text-body-1/75">
                            {{
                                tab === 'finance'
                                    ? 'Vincula las categorías del presupuesto para ver las finanzas de esta persona.'
                                    : tab === 'shopping'
                                      ? 'Vincula una lista de compras para ver y gestionar sus productos.'
                                      : 'Vincula los seguimientos para ver y registrar sus hábitos.'
                            }}
                        </p>
                        <button
                            class="workspace-button mx-auto mt-4"
                            @click="openLinks"
                        >
                            Vincular elementos
                        </button>
                    </div>
                </div>
                <aside
                    class="border-t border-body-1/10 pt-6 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0"
                >
                    <h2 class="mb-1 text-sm font-semibold">
                        Conectado a {{ profile.name }}
                    </h2>
                    <p class="mb-5 text-xs leading-5 text-body-1/70">
                        Información que ya tiene su lugar en Loger.
                    </p>
                    <button
                        v-if="connections.finance"
                        class="connection-row"
                        @click="setTab('finance')"
                    >
                        <Glyph name="wallet" /><span
                            ><b>{{ state.categoryGroup }}</b
                            ><small
                                >{{ categories.length }} categorías del
                                presupuesto</small
                            ></span
                        ><Glyph name="arrow" />
                    </button>
                    <button
                        v-if="connections.shopping"
                        class="connection-row"
                        @click="setTab('shopping')"
                    >
                        <Glyph name="basket" /><span
                            ><b>Su lista de compras</b
                            ><small
                                >{{
                                    purchases.filter((item) => !item.done)
                                        .length
                                }}
                                productos pendientes</small
                            ></span
                        ><Glyph name="arrow" />
                    </button>
                    <button
                        v-if="connections.tracking"
                        class="connection-row"
                        @click="setTab('tracking')"
                    >
                        <Glyph name="clock" /><span
                            ><b>Sus seguimientos</b
                            ><small
                                >{{ occurrences.length }} seguimientos
                                vinculados</small
                            ></span
                        ><Glyph name="arrow" />
                    </button>
                    <button class="connection-row" @click="setTab('boards')">
                        <Glyph name="book" /><span
                            ><b>{{ boards.length }} carpetas</b
                            ><small>Cuadernos y páginas</small></span
                        ><Glyph name="arrow" />
                    </button>
                    <button
                        class="mt-5 flex items-center gap-2 text-xs font-medium text-primary"
                        @click="openLinks"
                    >
                        <Glyph name="link" class="!h-4 !w-4" />Gestionar
                        conexiones
                    </button>
                    <div
                        v-if="tab === 'overview' && connections.tracking"
                        class="mt-8 border-t border-body-1/10 pt-5"
                    >
                        <p class="mb-3 text-xs font-semibold">
                            No perder de vista
                        </p>
                        <button
                            v-for="item in occurrences"
                            :key="item.name"
                            class="mb-3 flex w-full items-center gap-2 text-left text-sm text-body-1/75"
                            @click="setTab('tracking')"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="
                                    item.checked ? 'bg-success' : 'bg-warning'
                                "
                            ></span
                            >{{ item.name
                            }}<span class="ml-auto text-xs text-body-1/40">{{
                                item.checked ? "Revisado" : "Por revisar"
                            }}</span>
                        </button>
                    </div>
                </aside>
            </div>
        </main>
        <Modal :show="panel !== null" max-width="lg" @close="panel = null">
            <section class="bg-base-lvl-3 p-6 text-body-1">
                <header class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{
                                panel === "create"
                                    ? "Una nueva carpeta"
                                    : panel === "notebook"
                                      ? "Nuevo cuaderno"
                                      : "Conecta lo que ya existe"
                            }}
                        </h2>
                        <p class="mt-1 text-xs text-body-1/70">
                            Organiza la información vinculada a este perfil.
                        </p>
                    </div>
                    <button
                        aria-label="Cerrar"
                        class="px-2 text-xl text-body-1/70"
                        @click="panel = null"
                    >
                        ×
                    </button>
                </header>
                <form
                    v-if="panel === 'create'"
                    class="flex flex-col gap-5"
                    @submit.prevent="createBoard"
                >
                    <label class="flex flex-col gap-2 text-sm"
                        >Nombre de la carpeta<input
                            v-model="boardName"
                            required
                            autofocus
                            placeholder="Por ejemplo, actividades o documentos"
                            class="workspace-input"
                    /></label>
                    <fieldset>
                        <legend class="mb-2 text-sm">
                            Vista inicial de sus páginas
                        </legend>
                        <div class="grid grid-cols-3 gap-2">
                            <label
                                v-for="(label, key) in viewNames"
                                :key="key"
                                class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border p-4 text-xs"
                                :class="
                                    boardView === key
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-body-1/10 text-body-1/75'
                                "
                                ><input
                                    v-model="boardView"
                                    type="radio"
                                    :value="key"
                                    class="sr-only"
                                /><Glyph />{{ label }}</label
                            >
                        </div>
                        <p class="mt-3 text-xs text-body-1/70">
                            Puedes cambiar de vista después. Las páginas siguen
                            siendo las mismas.
                        </p>
                    </fieldset>
                    <button
                        class="workspace-button workspace-primary justify-center"
                        type="submit"
                    >
                        Nueva carpeta
                    </button>
                </form>
                <form
                    v-else-if="panel === 'notebook'"
                    class="flex flex-col gap-5"
                    @submit.prevent="createNotebook"
                >
                    <p class="text-sm text-body-1/75">
                        Se guardará dentro de {{ activeBoard?.name }}. Aquí
                        podrás añadir sus páginas.
                    </p>
                    <label class="flex flex-col gap-2 text-sm"
                        >Nombre del cuaderno<input
                            v-model="notebookName"
                            required
                            maxlength="150"
                            class="workspace-input"
                            placeholder="Por ejemplo, Test de articulación a la repetición"
                    /></label>
                    <p v-if="error" role="alert" class="text-sm text-danger">
                        {{ error }}
                    </p>
                    <button
                        type="submit"
                        class="workspace-button workspace-primary justify-center"
                        :disabled="saving"
                    >
                        Nuevo cuaderno
                    </button>
                </form>
                <div v-else class="flex flex-col gap-4">
                    <p class="text-sm leading-6 text-body-1/75">
                        El perfil reúne información de otros lugares, sin
                        duplicarla. Selecciona los elementos que deseas reunir
                        aquí. Quitar un vínculo conserva el elemento original.
                    </p>
                    <input
                        v-model="linkSearch"
                        class="workspace-input"
                        placeholder="Buscar categorías, tableros, listas y seguimientos"
                        aria-label="Buscar vínculos"
                    />
                    <div class="max-h-80 overflow-y-auto">
                        <label
                            v-for="link in filteredLinks"
                            :key="link.key"
                            class="connection-option"
                        >
                            <input
                                v-model="selectedLinks"
                                type="checkbox"
                                :value="link.key"
                            />
                            <span
                                >{{ link.name
                                }}<small>{{ link.label }}</small></span
                            >
                        </label>
                        <p
                            v-if="!filteredLinks.length"
                            class="text-sm text-body-1/70"
                        >
                            No se encontraron elementos.
                        </p>
                    </div>
                    <button
                        class="workspace-button workspace-primary justify-center"
                        :disabled="saving"
                        @click="saveLinks"
                    >
                        Guardar vínculos
                    </button>
                </div>
            </section>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.profile-tabs {
    scrollbar-width: none;
}
.profile-tabs::-webkit-scrollbar {
    display: none;
}
.workspace-button {
    @apply inline-flex min-h-[38px] items-center gap-2 rounded-lg border border-body-1/15 bg-base-lvl-3 px-3 py-2 text-xs font-medium transition hover:border-body-1/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary;
}
.workspace-primary {
    @apply border-primary bg-primary text-white hover:border-primary hover:bg-primary/90;
}
.workspace-input {
    @apply w-full rounded-lg border border-body-1/15 bg-base px-3 py-2 text-sm text-body-1 focus:border-primary focus:ring-primary;
}
.connection-row {
    @apply flex w-full items-center gap-3 border-b border-body-1/5 py-4 text-left text-body-1/70 transition hover:text-primary;
}
.connection-row > span {
    @apply min-w-0 flex-1;
}
.connection-row b {
    @apply block text-xs font-medium text-body-1/80;
}
.connection-row small {
    @apply mt-1 block text-[11px] leading-4 text-body-1/40;
}
.connection-row > svg:last-child {
    @apply h-3 w-3;
}
.connection-option {
    @apply flex items-start gap-3 rounded-lg border border-body-1/10 p-3 text-sm;
}
.connection-option input {
    @apply mt-1 rounded text-primary focus:ring-primary;
}
.connection-option small {
    @apply mt-1 block text-xs text-body-1/70;
}
</style>
