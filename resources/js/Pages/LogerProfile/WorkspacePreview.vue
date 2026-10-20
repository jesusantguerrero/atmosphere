<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Components/templates/AppLayout.vue";
import ProfilesPillarNav from "@/Components/templates/ProfilesPillarNav.vue";
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
const viewNames: Record<BoardView, string> = {
    notebook: "Páginas",
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
    if (
        await persist("patch", `/boards/${activeBoard.value.id}`, { view: key })
    )
        view.value = key;
};
const openBoard = (board: ProfileBoard) => {
    activeBoardId.value = board.id;
    view.value = board.view;
    stageId.value = "";
    pageId.value = "";
    void nextTick(() => boardHeader.value?.scrollIntoView({ block: "start" }));
};
const openNotebook = (notebook: ProfileStage) => {
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
    view.value = "notebook";
};
const newPage = async () => {
    if (!activeBoard.value || !selectedStage.value) return;
    const response = await persist(
        "post",
        `/boards/${activeBoard.value.id}/pages`,
        { stage_id: Number(selectedStage.value.id), title: "Nueva página" },
    );
    if (response) {
        pageId.value = response.item_id;
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
const setTab = (id: string) => {
    tab.value = id;
    activeBoardId.value = null;
};
</script>

<template>
    <AppLayout
        :title="profile.name"
        :show-back-button="true"
        @back="router.visit('/loger-profiles')"
    >
        <template #header><ProfilesPillarNav /></template>
        <main
            class="profile-workspace mx-auto mt-12 w-full max-w-7xl px-4 pb-12 pt-5 text-body-1 sm:px-6 lg:px-8"
        >
            <p role="status" class="mb-3 text-right text-xs text-body-1/50">
                {{ saveStatus }}
            </p>
            <p
                v-if="error"
                role="alert"
                class="mb-4 rounded-lg bg-danger/10 p-3 text-sm text-danger"
            >
                {{ error }}
            </p>
            <header
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
                            class="mb-1 text-xs text-body-1/60 hover:text-primary"
                            @click="router.visit('/loger-profiles')"
                        >
                            Perfiles /
                        </button>
                        <h1
                            class="break-words text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            {{ profile.name }}
                        </h1>
                        <p class="mt-1 text-sm text-body-1/60">
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
                aria-label="Secciones del perfil"
                class="profile-tabs mb-6 flex gap-6 overflow-x-auto overflow-y-hidden border-b border-body-1/10"
            >
                <button
                    v-for="item in tabs"
                    :key="item.id"
                    class="-mb-px flex shrink-0 items-center gap-2 border-b-2 pb-3 text-sm transition-colors"
                    :class="
                        tab === item.id && !activeBoard
                            ? 'border-primary font-semibold text-primary'
                            : 'border-transparent text-body-1/60 hover:text-body-1'
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
                    class="mb-5 flex scroll-mt-32 flex-wrap items-start justify-between gap-4"
                >
                    <div>
                        <button
                            class="mb-3 flex items-center gap-1 text-xs text-body-1/60 hover:text-primary"
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
                        <p class="mt-1 text-sm text-body-1/60">
                            {{
                                selectedStage
                                    ? activeBoard.name +
                                      " / " +
                                      selectedStage.name
                                    : activeBoard.description
                            }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="flex rounded-lg border border-body-1/10 bg-base-lvl-3 p-1"
                            v-if="selectedStage"
                            aria-label="Vista de las páginas"
                        >
                            <button
                                v-for="(label, key) in viewNames"
                                :key="key"
                                class="rounded-md px-3 py-1.5 text-xs"
                                :class="
                                    view === key
                                        ? 'bg-body-1/10 font-semibold text-body-1'
                                        : 'text-body-1/50'
                                "
                                :aria-pressed="view === key"
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
                            <p class="mt-1 text-sm text-body-1/60">
                                Cada cuaderno reúne sus propias páginas.
                            </p>
                        </div>
                        <span class="text-xs text-body-1/50"
                            >{{ activeBoard.stages.length }} cuadernos</span
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
                                class="mt-auto flex w-full items-center justify-between gap-2 text-xs text-body-1/60"
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
                            class="flex min-h-[180px] flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-body-1/20 p-5 text-sm text-body-1/60 hover:border-primary hover:text-primary"
                            @click="panel = 'notebook'"
                        >
                            <Glyph name="plus" />Crear cuaderno
                        </button>
                    </div>
                </section>
                <section
                    v-else-if="view === 'notebook'"
                    class="grid min-h-[460px] overflow-hidden rounded-xl border border-body-1/10 bg-base-lvl-3 lg:grid-cols-[230px_minmax(0,1fr)]"
                >
                    <aside
                        class="border-b border-body-1/10 bg-base-lvl-2/40 p-4 lg:border-b-0 lg:border-r"
                    >
                        <p class="mb-4 text-xs text-body-1/50">
                            Páginas de este cuaderno
                        </p>
                        <div
                            v-for="stage in [selectedStage]"
                            :key="stage.id"
                            class="mb-5"
                        >
                            <button
                                class="mb-2 text-sm font-semibold"
                                @click="
                                    stageId = stage.id;
                                    pageId = stage.items[0]?.id || '';
                                "
                            >
                                {{ stage.name }}
                            </button>
                            <div class="flex flex-col gap-1">
                                <button
                                    v-for="page in stage.items"
                                    :key="page.id"
                                    class="flex items-start gap-2 rounded-md px-2 py-2 text-left text-xs leading-5"
                                    :class="
                                        pageId === page.id
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-body-1/60 hover:bg-body-1/5'
                                    "
                                    @click="
                                        stageId = stage.id;
                                        pageId = page.id;
                                    "
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
                                    /><span>{{ page.title }}</span>
                                </button>
                            </div>
                        </div>
                    </aside>
                    <article
                        v-if="selectedPage"
                        class="min-w-0 px-5 py-7 sm:px-10 lg:px-12"
                    >
                        <div
                            class="mb-7 flex flex-wrap items-center justify-between gap-3 text-xs text-body-1/50"
                        >
                            <span
                                >{{ activeBoard.name }} /
                                {{ selectedStage?.name }}</span
                            ><label class="flex items-center gap-2"
                                >Mostrar como<select
                                    v-model="selectedPage.kind"
                                    @change="savePage(selectedPage)"
                                    aria-label="Tipo de página"
                                    :disabled="saving"
                                    class="rounded-md border border-body-1/10 bg-base px-2 py-1 text-body-1"
                                >
                                    <option value="note">Página</option>
                                    <option value="task">Tarea</option>
                                    <option value="event">Evento</option>
                                </select></label
                            >
                        </div>
                        <input
                            v-model="selectedPage.title"
                            @change="savePage(selectedPage)"
                            aria-label="Título de la página"
                            :disabled="saving"
                            class="w-full border-0 bg-transparent p-0 text-2xl font-semibold leading-tight focus:ring-0 sm:text-3xl"
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
                        <label
                            v-if="selectedPage.kind === 'event'"
                            class="mt-5 flex flex-col gap-2 text-sm text-body-1/60"
                            >Cuándo<input
                                v-model="selectedPage.when"
                                type="datetime-local"
                                :disabled="saving"
                                @change="savePage(selectedPage)"
                                placeholder="Añadir fecha y hora"
                                class="workspace-input"
                        /></label>
                        <textarea
                            v-model="selectedPage.body"
                            @change="savePage(selectedPage)"
                            aria-label="Contenido de la página"
                            :disabled="saving"
                            placeholder="Empieza a escribir aquí…"
                            class="mt-6 min-h-[240px] w-full resize-y border-0 bg-transparent p-0 text-sm leading-7 text-body-1/80 focus:ring-0"
                        ></textarea>
                        <button
                            class="workspace-button workspace-primary"
                            :disabled="saving"
                            @click="savePage(selectedPage)"
                        >
                            Guardar página
                        </button>
                        <p
                            class="mt-4 border-t border-body-1/10 pt-4 text-xs text-body-1/40"
                        >
                            Esta página pertenece a {{ selectedStage?.name }},
                            dentro de {{ activeBoard.name }}, y está vinculada a
                            {{ profile.name }}.
                        </p>
                    </article>
                    <div
                        v-else
                        class="flex flex-col items-center justify-center gap-4 p-10 text-center"
                    >
                        <Glyph class="!h-8 !w-8 text-body-1/30" />
                        <p class="text-sm text-body-1/60">
                            Tu primera página empieza aquí.
                        </p>
                        <button class="workspace-button" @click="newPage">
                            Crear página
                        </button>
                    </div>
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
                                <span class="text-xs text-body-1/50">{{
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
                                    class="mt-4 text-xs text-body-1/50"
                                >
                                    {{ displayWhen(page.when) }}
                                </p>
                            </button>
                        </div>
                        <button
                            class="mt-3 w-full rounded-md py-2 text-left text-xs text-body-1/50 hover:text-primary"
                            @click="
                                stageId = stage.id;
                                newPage();
                            "
                        >
                            + Añadir página
                        </button>
                    </div>
                </section>
                <section
                    v-else
                    class="overflow-hidden rounded-xl border border-body-1/10 bg-base-lvl-3"
                >
                    <template v-for="stage in [selectedStage]" :key="stage.id"
                        ><h3
                            class="bg-base-lvl-2/40 px-5 py-3 text-xs font-semibold text-body-1/60"
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
                                    <p class="mt-1 text-xs text-body-1/50">
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
                                <span class="text-xs text-body-1/50"
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
                                    >{{ page.title }}</span
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
                                <p class="mt-1 text-xs text-body-1/50">
                                    Un espacio para cada parte de su vida.
                                </p>
                            </div>
                            <button
                                class="text-xs font-medium text-primary"
                                @click="panel = 'create'"
                            >
                                + Crear carpeta
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
                                    class="mt-1 text-xs leading-5 text-body-1/50"
                                >
                                    {{ board.description }}
                                </p>
                                <div
                                    class="mt-auto flex w-full items-center justify-between gap-2 pt-5 text-xs text-body-1/50"
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
                                        páginas</span
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
                                <Glyph name="wallet" class="text-body-1/50" />Su
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
                                    class="text-sm text-body-1/50"
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
                                        <p class="mt-1 text-xs text-body-1/50">
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
                                    class="text-body-1/50"
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
                        <p class="mb-5 text-sm text-body-1/50">
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
                                <p class="mt-1 text-sm text-body-1/50">
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
                        <p class="text-sm text-body-1/60">
                            Todavía no hay elementos vinculados en esta sección.
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
                    <p class="mb-5 text-xs leading-5 text-body-1/50">
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
                            class="mb-3 flex w-full items-center gap-2 text-left text-sm text-body-1/60"
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
                        <p class="mt-1 text-xs text-body-1/50">
                            Organiza la información vinculada a este perfil.
                        </p>
                    </div>
                    <button
                        aria-label="Cerrar"
                        class="px-2 text-xl text-body-1/50"
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
                                        : 'border-body-1/10 text-body-1/60'
                                "
                                ><input
                                    v-model="boardView"
                                    type="radio"
                                    :value="key"
                                    class="sr-only"
                                /><Glyph />{{ label }}</label
                            >
                        </div>
                        <p class="mt-3 text-xs text-body-1/50">
                            Puedes cambiar de vista después. Las páginas siguen
                            siendo las mismas.
                        </p>
                    </fieldset>
                    <button
                        class="workspace-button workspace-primary justify-center"
                        type="submit"
                    >
                        Crear carpeta
                    </button>
                </form>
                <form
                    v-else-if="panel === 'notebook'"
                    class="flex flex-col gap-5"
                    @submit.prevent="createNotebook"
                >
                    <p class="text-sm text-body-1/60">
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
                        Crear cuaderno
                    </button>
                </form>
                <div v-else class="flex flex-col gap-4">
                    <p class="text-sm leading-6 text-body-1/60">
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
                            class="text-sm text-body-1/50"
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
    @apply flex w-full items-center gap-3 border-b border-body-1/5 py-4 text-left text-body-1/50 transition hover:text-primary;
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
    @apply mt-1 block text-xs text-body-1/50;
}
</style>
