<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

import axios from "axios";

import { useI18n } from "vue-i18n";
import { NSelect } from "naive-ui";

const { t } = useI18n();

interface Item {
    id: number;

    title: string;

    state: "pending" | "buy" | "skip";

    is_done: boolean;

    order: number;
}

interface Stage {
    id: number;

    name: string;

    items: Item[];
}

interface Review {
    id: string | null;

    status: "none" | "ready" | "changed" | "completed";

    note: string;

    reviewed_at: string | null;

    completed_at: string | null;

    current_version: string;
    reviewed_ids?: number[];
    item_details?: Record<string, { quantity: string; note: string }>;
}

interface ShoppingSession {
    id: string;
    started_at: string;
    items: { id: number; title: string; category?: string }[];
    purchased_ids: number[];
}
interface ShoppingVisit extends ShoppingSession {
    completed_at: string;
    total: string;
    currency: string;
    note: string;
    purchased_items: { id: number; title: string; category?: string }[];
    remaining_items: { id: number; title: string }[];
}
interface PlanPayload {
    session?: ShoppingSession | null;
    visits?: ShoppingVisit[];
    review?: Review;

    id: number;

    name: string;

    stages: Stage[];
}

interface ListSummary {
    id: number;

    name: string;

    total: number;

    pending: number;

    shared: boolean;
}

const props = defineProps<{
    plan: PlanPayload;

    /** Endpoint base for mutations. Authed view uses `/shopping/{plan}`,

     *  public view uses `/shared/list/{token}`. */

    apiBase: string;

    /** Mercure subscribe URL keyed by share-token, when sharing is on. */

    mercureUrl?: string | null;

    /** When true, renders the share / reset / settings buttons in the header. */

    showOwnerControls?: boolean;

    inventoryMode?: boolean;

    /** Endpoint paths differ slightly between authed/shared views. */

    endpoints: {
        cycle: (itemId: number) => string;

        add: string;

        reset: string;

        review?: string;

        confirmReview?: string;

        completeReview?: string;

        destroy?: (itemId: number) => string;
    };

    /** Owner-only: all lists for the header switcher. Absent in the shared view. */

    lists?: ListSummary[];

    activeListId?: number;
}>();

const emit = defineEmits<{
    (e: "switch", id: number): void;

    (e: "create", name: string): void;

    (e: "rename", payload: { id: number; name: string }): void;

    (e: "delete", id: number): void;

    (e: "import", payload: { name: string; text: string }): void;
}>();

const showListMenu = ref(false);

const showImport = ref(false);

const importName = ref("");

const importText = ref("");

const chooseList = (id: number) => {
    showListMenu.value = false;

    emit("switch", id);
};

const promptCreate = () => {
    showListMenu.value = false;

    const name = window.prompt("Name of the new list?");

    if (name && name.trim()) emit("create", name.trim());
};

const promptRename = () => {
    showListMenu.value = false;

    const name = window.prompt("Rename list", props.plan.name);

    if (name && name.trim())
        emit("rename", { id: props.plan.id, name: name.trim() });
};

const confirmDelete = () => {
    showListMenu.value = false;

    if (
        window.confirm(
            t('Delete "{name}" and all its items?', { name: props.plan.name }),
        )
    )
        emit("delete", props.plan.id);
};

const openImport = () => {
    showListMenu.value = false;

    importName.value = "";

    importText.value = "";

    showImport.value = true;
};

const submitImport = () => {
    if (!importText.value.trim()) return;

    emit("import", { name: importName.value.trim(), text: importText.value });

    showImport.value = false;
};

const stages = ref<Stage[]>(JSON.parse(JSON.stringify(props.plan.stages)));

const composer = ref<string>("");
const productCategory = ref<number | string>(props.plan.stages[0]?.id ?? "");
const productCategoryName = ref("");

const stageName = (name: string) =>
    /^[A-Z][A-Z0-9_]+$/.test(name) ? "Otros productos" : name;
const categoryOptions = computed(() => [
    ...stages.value.map((s) => ({ label: stageName(s.name), value: s.id })),
    { label: "+ Nueva categoría", value: "new" as unknown as number },
]);
const moveCategoryOptions = computed(() =>
    stages.value.map((s) => ({ label: stageName(s.name), value: s.id })),
);

const inventorySearch = computed(() =>
    props.inventoryMode ? composer.value : "",
);
const normalizeProduct = (value: string) =>
    value
        .trim()
        .replace(/\s+/g, " ")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLocaleLowerCase();

const saveError = ref("");

const updatingItems = ref<number[]>([]);

const sending = ref(false);

const review = ref<Review | null>(props.plan.review ?? null);

const reviewNote = ref(
    props.plan.review?.status === "completed"
        ? ""
        : (props.plan.review?.note ?? ""),
);

const reviewBusy = ref(false);

const reviewDialog = ref<HTMLDialogElement | null>(null);
const openReview = () => {
    reviewError.value = "";

    reviewDialog.value?.showModal();
};

const reviewError = ref("");
const shoppingSession = ref<ShoppingSession | null>(props.plan.session ?? null);
const visits = ref<ShoppingVisit[]>(props.plan.visits ?? []);
const sessionBusy = ref(false);
const sessionError = ref("");
const sessionMessage = ref("");
const finishDialog = ref<HTMLDialogElement | null>(null);
const historyDialog = ref<HTMLDialogElement | null>(null);
const selectedVisit = ref<ShoppingVisit | null>(null);
const visitAmountElement = ref<HTMLElement | null>(null);
const headerShowsAmount = ref(false);
const handleHistoryScroll = (event: Event) => {
    const scroller = event.currentTarget as HTMLElement;
    headerShowsAmount.value =
        !!selectedVisit.value &&
        !!visitAmountElement.value &&
        visitAmountElement.value.getBoundingClientRect().bottom <=
            scroller.getBoundingClientRect().top;
};
const formatVisitAmount = (value: string) =>
    new Intl.NumberFormat("es-DO", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));
const formatVisitDate = (value: string) =>
    new Intl.DateTimeFormat("es-DO", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(new Date(value));
const formatVisitTime = (value: string) =>
    new Intl.DateTimeFormat("es-DO", {
        hour: "numeric",
        minute: "2-digit",
    }).format(new Date(value));
const visitGroups = computed(() => {
    const groups = new Map<string, ShoppingVisit["purchased_items"]>();
    for (const item of selectedVisit.value?.purchased_items ?? []) {
        const category =
            item.category && !/^[A-Z][A-Z0-9_]+$/.test(item.category)
                ? item.category
                : "Otros productos";
        if (!groups.has(category)) groups.set(category, []);
        groups.get(category)!.push(item);
    }
    return Array.from(groups, ([name, items]) => ({ name, items }));
});
const openHistory = () => {
    selectedVisit.value = null;
    headerShowsAmount.value = false;
    historyDialog.value?.showModal();
};
const viewVisit = (visit: ShoppingVisit) => {
    selectedVisit.value = visit;
    headerShowsAmount.value = false;
};
const backToHistory = () => {
    selectedVisit.value = null;
    headerShowsAmount.value = false;
};
const visitTotal = ref("");
const visitCurrency = ref("DOP");
const visitNote = ref("");
const acceptShoppingPlan = (plan: PlanPayload) => {
    stages.value = plan.stages;
    review.value = plan.review ?? null;
    shoppingSession.value = plan.session ?? null;
    visits.value = plan.visits ?? [];
};
const startShopping = async () => {
    if (sessionBusy.value) return;
    sessionBusy.value = true;
    sessionError.value = "";
    sessionMessage.value = "";
    try {
        const { data } = await axios.post(
            `${props.apiBase}/sessions/start`,
            {},
            requestConfig(),
        );
        acceptShoppingPlan(data.plan);
    } catch (error: any) {
        sessionError.value =
            error.response?.data?.message ?? "No se pudo iniciar la compra.";
    } finally {
        sessionBusy.value = false;
    }
};
const openFinishShopping = () => {
    sessionError.value = "";
    finishDialog.value?.showModal();
};
const finishShopping = async () => {
    if (
        !shoppingSession.value ||
        sessionBusy.value ||
        updatingItems.value.length
    )
        return;
    sessionBusy.value = true;
    sessionError.value = "";
    try {
        const { data } = await axios.post(
            `${props.apiBase}/sessions/finish`,
            {
                session_id: shoppingSession.value.id,
                total: visitTotal.value,
                currency: visitCurrency.value,
                note: visitNote.value,
            },
            requestConfig(),
        );
        acceptShoppingPlan(data.plan);
        finishDialog.value?.close();
        visitTotal.value = "";
        visitNote.value = "";
        sessionMessage.value =
            "Visita guardada. La lista está lista para la próxima revisión.";
    } catch (error: any) {
        sessionError.value =
            error.response?.data?.message ??
            "No se pudo terminar la compra. Inténtalo otra vez.";
    } finally {
        sessionBusy.value = false;
    }
};
const confirmationMessage = ref("");
const isReviewed = (item: Item) =>
    (review.value?.reviewed_ids ?? []).includes(item.id);
const uncheckedCount = computed(
    () => allItems.value.filter((item) => !isReviewed(item)).length,
);
const guestCounts = computed(() => ({
    needed: allItems.value.filter(
        (item) => isReviewed(item) && item.state === "pending",
    ).length,
    available: allItems.value.filter(
        (item) => isReviewed(item) && item.state !== "pending",
    ).length,
}));
const categoryDialog = ref<HTMLDialogElement | null>(null);
const movingItem = ref<Item | null>(null);
const movingCategory = ref<number | null>(null);
const categoryBusy = ref(false);
const itemQuantity = ref("");
const itemNote = ref("");
const onlyUnchecked = ref(false);
const lastMark = ref<{
    id: number;
    title: string;
    state: Item["state"];
    reviewed: boolean;
} | null>(null);
const undoBusy = ref(false);
const undoError = ref("");
const undoMark = async () => {
    if (!lastMark.value || undoBusy.value || updatingItems.value.length) return;
    undoBusy.value = true;
    undoError.value = "";
    const mark = lastMark.value;
    try {
        const { data } = await axios.post(
            props.endpoints.cycle(mark.id),
            { state: mark.state, reviewed: mark.reviewed },
            requestConfig(),
        );
        applyItemUpdate({
            id: mark.id,
            state: mark.state,
            is_done: mark.state === "buy",
        });
        if (data.review) review.value = data.review;
        lastMark.value = null;
    } catch {
        undoError.value = "No se pudo deshacer. Inténtalo otra vez.";
    } finally {
        undoBusy.value = false;
    }
};
const categoryError = ref("");
const openMoveCategory = (item: Item, stageId: number) => {
    movingItem.value = item;
    movingCategory.value = stageId;
    const details = review.value?.item_details?.[item.id];
    itemQuantity.value = details?.quantity ?? "";
    itemNote.value = details?.note ?? "";
    categoryError.value = "";
    categoryDialog.value?.showModal();
};
const saveMoveCategory = async () => {
    if (!movingItem.value || !movingCategory.value || categoryBusy.value)
        return;
    categoryBusy.value = true;
    try {
        const { data } = await axios.patch(
            `${props.apiBase}/items/${movingItem.value.id}/category`,
            {
                stage_id: movingCategory.value,
                quantity: itemQuantity.value,
                note: itemNote.value,
            },
            requestConfig(),
        );
        acceptShoppingPlan(data.plan);
        categoryDialog.value?.close();
    } catch {
        categoryError.value =
            "No se pudo cambiar la categoría. Inténtalo otra vez.";
    } finally {
        categoryBusy.value = false;
    }
};

const reviewTitle = computed(
    () =>
        ({
            none: "Confirmar esta compra",
            ready: "Lista lista para comprar",
            changed: "Cambios posteriores a la confirmación",
            completed: "Compra realizada",
        })[review.value?.status ?? "none"],
);

const reviewDate = computed(() => {
    const value = review.value?.completed_at ?? review.value?.reviewed_at;

    return value
        ? new Date(value).toLocaleString("es-DO", {
              dateStyle: "short",
              timeStyle: "short",
          })
        : "";
});

const refreshReview = async () => {
    if (!props.endpoints.review) return;

    reviewBusy.value = true;

    reviewError.value = "";

    try {
        const { data } = await axios.get(props.endpoints.review);

        stages.value = data.plan.stages;

        review.value = data.plan.review;
    } catch {
        reviewError.value = "No se pudo actualizar. Inténtalo otra vez.";
    } finally {
        reviewBusy.value = false;
    }
};

const submitReview = async () => {
    if (
        !review.value ||
        reviewBusy.value ||
        updatingItems.value.length ||
        sending.value
    )
        return;

    const endpoint = props.inventoryMode
        ? props.endpoints.confirmReview
        : props.endpoints.completeReview;

    if (!endpoint) return;

    reviewBusy.value = true;

    reviewError.value = "";

    try {
        const { data } = await axios.post(
            endpoint,
            props.inventoryMode
                ? {
                      note: reviewNote.value,
                      current_version: review.value.current_version,
                  }
                : { review_id: review.value.id },
            requestConfig(),
        );

        review.value = data.review;

        reviewDialog.value?.close();
        if (props.inventoryMode)
            confirmationMessage.value = "Listo, ya le avisamos a quien va a comprar.";

        if (data.review.status === "completed") reviewNote.value = "";
    } catch (error: any) {
        if (error.response?.status === 409) {
            await refreshReview();

            reviewError.value =
                "La lista cambió. Revisa los cambios antes de confirmar.";
        } else {
            reviewError.value = "No se pudo confirmar. Inténtalo otra vez.";
        }
    } finally {
        reviewBusy.value = false;
    }
};

watch(
    () => props.plan,

    (next) => {
        stages.value = JSON.parse(JSON.stringify(next.stages));

        review.value = next.review ?? null;
        shoppingSession.value = next.session ?? null;
        visits.value = next.visits ?? [];

        reviewNote.value =
            next.review?.status === "completed"
                ? ""
                : (next.review?.note ?? "");
    },

    { deep: true },
);

const allItems = computed<Item[]>(() => stages.value.flatMap((s) => s.items));

// LM-9: every state stays visible until "reset trip" — supermarket flow is

// mark-while-you-shop, confirm-at-the-end. Items group: pending (still to buy)

// → buy (already in cart) → skip (not getting today). The previous filter tabs

// hard-removed items on tap, which felt like the row was being deleted.

const STATE_ORDER = { pending: 0, buy: 1, skip: 2 } as const;

// The default list's raw name is a system constant (e.g. SHOPPING_LIST); show a

// friendly translated title instead — this is the only screen a shared guest sees.

const listTitle = computed(() => {
    const n = (props.plan.name ?? "").trim();

    return /^[A-Z][A-Z0-9_]+$/.test(n) ? t("Shopping list") : n;
});

const visibleItems = computed<Item[]>(() =>
    [...allItems.value].sort((a, b) => {
        const sa = STATE_ORDER[a.state];

        const sb = STATE_ORDER[b.state];

        if (sa !== sb) return sa - sb;

        return a.order - b.order;
    }),
);

// Categories = stages. Section headers show only when >1 stage; a single

// default stage renders as a flat list (unchanged from before).

const hasCategories = computed(() => stages.value.length > 1);

const visibleSections = computed(() =>
    stages.value

        .map((stage) => ({
            id: stage.id,

            name: /^[A-Z][A-Z0-9_]+$/.test(stage.name)
                ? "Otros productos"
                : stage.name,

            items: [...stage.items]

                .filter(
                    (item) =>
                        (!shoppingSession.value ||
                            props.inventoryMode ||
                            shoppingSession.value.items.some(
                                (entry) => entry.id === item.id,
                            )) &&
                        (!props.inventoryMode ||
                            (normalizeProduct(item.title).includes(
                                normalizeProduct(inventorySearch.value),
                            ) &&
                                (!onlyUnchecked.value || !isReviewed(item)))),
                )

                .sort((a, b) =>
                    props.inventoryMode || shoppingSession.value
                        ? a.order - b.order
                        : STATE_ORDER[a.state] - STATE_ORDER[b.state] ||
                          a.order - b.order,
                ),
        }))

        .filter(
            (stage) =>
                (!props.inventoryMode && !shoppingSession.value) ||
                stage.items.length > 0,
        ),
);

const existingProduct = computed(() =>
    props.inventoryMode
        ? allItems.value.find(
              (item) =>
                  normalizeProduct(item.title) ===
                  normalizeProduct(composer.value),
          )
        : undefined,
);

const counts = computed(() => ({
    total: allItems.value.length,

    pending: allItems.value.filter((i) => i.state === "pending").length,

    buy: allItems.value.filter((i) => i.state === "buy").length,

    skip: allItems.value.filter((i) => i.state === "skip").length,
}));

const findItem = (id: number) => allItems.value.find((i) => i.id === id);

const applyItemUpdate = (next: Partial<Item> & { id: number }) => {
    for (const stage of stages.value) {
        const idx = stage.items.findIndex((i) => i.id === next.id);

        if (idx !== -1) {
            stage.items[idx] = { ...stage.items[idx], ...next };

            return;
        }
    }
};

const removeItemLocally = (id: number) => {
    for (const stage of stages.value) {
        stage.items = stage.items.filter((i) => i.id !== id);
    }
};

const addItemLocally = (item: Item) => {
    if (findItem(item.id)) return;

    let stage = stages.value.find((stage) => stage.id === item.stage_id);
    if (!stage && item.stage_id && item.stage_name) {
        stage = { id: item.stage_id, name: item.stage_name, items: [] };
        stages.value.push(stage);
    }
    (stage ?? stages.value[0])?.items.push(item);
};

// ─── Stage (category) CRUD ──────────────────────────────────────

const newCategoryName = ref("");

const isAddingCategory = ref(false);

const renamingStageId = ref<number | null>(null);

const renamingName = ref("");

const confirmDeleteStageId = ref<number | null>(null);

const startAddCategory = () => {
    newCategoryName.value = "";

    isAddingCategory.value = true;
};

const commitAddCategory = async () => {
    const name = newCategoryName.value.trim();

    isAddingCategory.value = false;

    if (!name) return;

    try {
        const { data } = await axios.post(`${props.apiBase}/stages`, {
            name,
        });

        stages.value.push({ id: data.id, name: data.name, items: [] });
    } catch {
        // silent
    }
};

const startRename = (stage: Stage) => {
    renamingStageId.value = stage.id;

    renamingName.value = stage.name;
};

const commitRename = async (stage: Stage) => {
    const name = renamingName.value.trim();

    renamingStageId.value = null;

    if (!name || name === stage.name) return;

    try {
        await axios.patch(`${props.apiBase}/stages/${stage.id}`, { name });

        stage.name = name;
    } catch {
        // silent
    }
};

const cancelRename = () => {
    renamingStageId.value = null;
};

const deleteCategory = async (stage: Stage) => {
    confirmDeleteStageId.value = null;

    const fallback = stages.value.find((s) => s.id !== stage.id);

    try {
        await axios.delete(`${props.apiBase}/stages/${stage.id}`);

        if (fallback) fallback.items.push(...stage.items);

        stages.value = stages.value.filter((s) => s.id !== stage.id);
    } catch {
        // silent
    }
};

const desiredStates = new Map<number, Item["state"]>();

const cycleItem = async (item: Item, availability?: boolean) => {
    const previous = findItem(item.id)?.state ?? item.state;
    if (props.inventoryMode) {
        lastMark.value = {
            id: item.id,
            title: item.title,
            state: previous,
            reviewed: isReviewed(item),
        };
        confirmationMessage.value = "";
    }

    const next: Item["state"] = props.inventoryMode
        ? (availability ?? previous === "pending")
            ? "skip"
            : "pending"
        : shoppingSession.value
          ? previous === "buy"
              ? "pending"
              : "buy"
          : previous === "pending"
            ? "buy"
            : previous === "buy"
              ? "skip"
              : "pending";

    desiredStates.set(item.id, next);

    applyItemUpdate({ id: item.id, state: next, is_done: next === "buy" });

    if (updatingItems.value.includes(item.id)) return;

    updatingItems.value.push(item.id);

    saveError.value = "";

    let confirmed = previous;

    try {
        while (desiredStates.has(item.id)) {
            const target = desiredStates.get(item.id)!;

            desiredStates.delete(item.id);

            try {
                const response = await axios.post(
                    props.endpoints.cycle(item.id),

                    { state: target },

                    requestConfig(),
                );

                if (response?.data?.review) review.value = response.data.review;

                if (
                    !props.inventoryMode &&
                    response?.data &&
                    "session" in response.data
                )
                    shoppingSession.value = response.data.session;
                confirmed = target;
            } catch {
                if (!desiredStates.has(item.id)) {
                    applyItemUpdate({
                        id: item.id,

                        state: confirmed,

                        is_done: confirmed === "buy",
                    });
                }

                saveError.value =
                    "No se pudo guardar. Vuelve a marcar el producto.";
            }
        }
    } finally {
        updatingItems.value = updatingItems.value.filter(
            (id) => id !== item.id,
        );
    }
};

const submitComposer = async () => {
    const title = composer.value.trim();

    if (!title || sending.value || existingProduct.value) return;
    if (
        props.inventoryMode &&
        productCategory.value === "new" &&
        !productCategoryName.value.trim()
    ) {
        saveError.value = "Escribe el nombre de la categoría.";
        return;
    }

    sending.value = true;

    saveError.value = "";

    try {
        const response = await axios.post(
            props.endpoints.add,

            {
                title,
                ...(props.inventoryMode
                    ? productCategory.value === "new"
                        ? { category_name: productCategoryName.value.trim() }
                        : { stage_id: productCategory.value || undefined }
                    : {}),
            },

            requestConfig(),
        );

        if (response.data.review) review.value = response.data.review;

        const created: Item = {
            id: response.data.id,

            title: response.data.title,

            state: response.data.state ?? "pending",

            is_done: response.data.state === "buy",

            order: response.data.order ?? allItems.value.length + 1,
        };

        if (response.data.stage) {
            let stage = stages.value.find(
                (stage) => stage.id === response.data.stage.id,
            );
            if (!stage) {
                stage = { ...response.data.stage, items: [] };
                stages.value.push(stage!);
            }
            if (!allItems.value.some((item) => item.id === created.id))
                stage!.items.push(created);
            productCategory.value = response.data.stage.id;
            productCategoryName.value = "";
        } else {
            addItemLocally(created);
        }

        composer.value = "";
    } catch {
        saveError.value = "No se pudo agregar el producto. Inténtalo otra vez.";
    } finally {
        sending.value = false;
    }
};

const removeItem = async (item: Item) => {
    if (!props.endpoints.destroy) return;

    if (
        !window.confirm(
            t('Remove "{title}" from this list?', { title: item.title }),
        )
    )
        return;

    const snapshot = stages.value.map((s) => ({ ...s, items: [...s.items] }));

    removeItemLocally(item.id);

    try {
        const response = await axios.delete(
            props.endpoints.destroy(item.id),
            requestConfig(),
        );

        if (response.data.review) review.value = response.data.review;
    } catch {
        stages.value = snapshot;
    }
};

const reset = async () => {
    if (
        !window.confirm(
            t("Reset every item to pending? This starts a fresh trip."),
        )
    )
        return;

    const response = await axios.post(
        props.endpoints.reset,
        {},
        requestConfig(),
    );

    if (response.data.review) review.value = response.data.review;

    for (const stage of stages.value) {
        for (const item of stage.items) {
            item.state = "pending";

            item.is_done = false;
        }
    }
};

const stateClass = (state: Item["state"]) => {
    if (state === "buy") return "bg-success/10 text-body border-success/40";

    if (state === "skip")
        return "bg-error/5 text-body-1/50 border-error/30 line-through";

    return "bg-base-lvl-2 text-body border-base";
};

const stateIcon = (state: Item["state"]) => {
    if (state === "buy") return "fa-check text-success";

    if (state === "skip") return "fa-xmark text-error";

    return "fa-circle text-body-1/30";
};

// Tag the OneSignal subscription with this list's share token so the backend

// listener can target push notifications to everyone viewing this list. We

// only tag when sharing is enabled (i.e. there's a token to address). The

// global OneSignal SDK is loaded by app.blade.php and exposed on `window`.

const oneSignalTagKey = "shopping_list";

const tagToken = computed<string | null>(() => {
    if (!props.mercureUrl) return null;

    const match = props.mercureUrl.match(/\/shared\/list\/([^?&/]+)/);

    return match ? match[1] : null;
});

declare global {
    interface Window {
        OneSignalDeferred?: Array<(os: any) => Promise<void> | void>;
    }
}

// We capture this device's OneSignal subscription ID so the server can pass

// it to OneSignal as `excluded_subscription_ids` when broadcasting our own

// changes — otherwise we'd echo a push back to the device that just made the

// change. Stored in a ref so axios picks up changes when the SDK resolves.

const oneSignalSubscriptionId = ref<string | null>(null);

const captureOneSignalSubscriptionId = () => {
    if (typeof window === "undefined" || !window.OneSignalDeferred) return;

    window.OneSignalDeferred.push(async (OneSignal: any) => {
        try {
            const id = OneSignal?.User?.PushSubscription?.id;

            if (id) oneSignalSubscriptionId.value = String(id);

            // The id may not be available right away (subscription not opted-in

            // yet). Listen for the `change` event so we capture it on opt-in.

            OneSignal?.User?.PushSubscription?.addEventListener?.(
                "change",

                (event: any) => {
                    const next =
                        event?.current?.id ??
                        OneSignal?.User?.PushSubscription?.id ??
                        null;

                    oneSignalSubscriptionId.value = next ? String(next) : null;
                },
            );
        } catch {
            // Silent — exclusion is best-effort.
        }
    });
};

const tagOneSignal = (token: string) => {
    if (typeof window === "undefined" || !window.OneSignalDeferred) return;

    window.OneSignalDeferred.push(async (OneSignal: any) => {
        try {
            await OneSignal.User.addTag(oneSignalTagKey, token);
        } catch {
            // Silent — push is best-effort; nothing should break shopping.
        }
    });
};

const untagOneSignal = () => {
    if (typeof window === "undefined" || !window.OneSignalDeferred) return;

    window.OneSignalDeferred.push(async (OneSignal: any) => {
        try {
            await OneSignal.User.removeTag(oneSignalTagKey);
        } catch {
            // Silent — see above.
        }
    });
};

/** Adds the subscription-ID exclusion header so the server can suppress an

 *  echo push back to this device. Returns config object for axios. */

const requestConfig = () => {
    const headers: Record<string, string> = {};

    if (oneSignalSubscriptionId.value) {
        headers["X-OneSignal-Subscription-Id"] = oneSignalSubscriptionId.value;
    }

    return { headers };
};

// Mercure subscription — re-applies updates from other tabs/devices in real time.

let eventSource: EventSource | null = null;

const wireMercure = () => {
    if (
        !props.mercureUrl ||
        typeof window === "undefined" ||
        typeof EventSource === "undefined"
    )
        return;

    eventSource = new EventSource(props.mercureUrl);

    eventSource.onmessage = (event) => {
        try {
            const payload = JSON.parse(event.data);

            if (payload.review) review.value = payload.review;

            if (
                payload.action === "reset" ||
                payload.action === "visit_completed"
            ) {
                for (const stage of stages.value) {
                    for (const item of stage.items) {
                        item.state = "pending";

                        item.is_done = false;
                    }
                }

                return;
            }

            if (payload.action === "deleted" && payload.item) {
                removeItemLocally(payload.item.id);

                return;
            }

            if (payload.action === "created" && payload.item) {
                addItemLocally({
                    id: payload.item.id,
                    stage_id: payload.item.stage_id,
                    stage_name: payload.item.stage_name,

                    title: payload.item.title,

                    state: payload.item.state ?? "pending",

                    is_done: payload.item.is_done ?? false,

                    order: payload.item.order ?? 0,
                });

                return;
            }

            if (payload.action === "updated" && payload.item) {
                const currentStage = stages.value.find((stage) =>
                    stage.items.some((item) => item.id === payload.item.id),
                );
                if (
                    payload.item.stage_id &&
                    currentStage?.id !== payload.item.stage_id
                ) {
                    const item = findItem(payload.item.id);
                    removeItemLocally(payload.item.id);
                    addItemLocally({ ...item, ...payload.item });
                    return;
                }
                applyItemUpdate({
                    id: payload.item.id,

                    state: payload.item.state,

                    is_done: payload.item.is_done,

                    title: payload.item.title,
                });
            }
        } catch {
            // Ignore malformed payloads — never break UI on a bad event.
        }
    };

    eventSource.onerror = () => {
        // Mercure hub may not be running (dev). Silently degrade: the
        // page still works, just without push updates from other tabs.
    };
};

const refreshOnFocus = () => {
    if (
        !updatingItems.value.length &&
        !sending.value &&
        !reviewBusy.value &&
        !sessionBusy.value &&
        !categoryBusy.value &&
        !composer.value.trim() &&
        !reviewDialog.value?.open &&
        !finishDialog.value?.open &&
        !categoryDialog.value?.open
    )
        refreshReview();
};
onMounted(() => {
    window.addEventListener("focus", refreshOnFocus);
    wireMercure();

    captureOneSignalSubscriptionId();

    if (tagToken.value) tagOneSignal(tagToken.value);
});

watch(tagToken, (next, prev) => {
    if (next && next !== prev) tagOneSignal(next);

    if (!next && prev) untagOneSignal();
});

onBeforeUnmount(() => {
    window.removeEventListener("focus", refreshOnFocus);
    eventSource?.close();

    eventSource = null;

    untagOneSignal();
});
</script>

<template>
    <!-- Pin to the available viewport area between AppLayout's top header

         (h-16 = 64px) and the mobile bottom-nav (h-14 = 56px, hidden on lg).

         Using fixed positioning so the chat composer at the bottom of this

         flex column stays visible without the user having to scroll past

         the AppShell chrome to reach it. -->

    <div
        class="fixed inset-x-0 flex flex-col bg-base-lvl-1 z-10"
        :class="inventoryMode ? 'inset-y-0' : 'top-16 bottom-14 lg:bottom-0'"
    >
        <!-- Header: list name + filter chips + owner controls -->

        <header class="bg-base-lvl-3 border-b border-base">
            <div class="max-w-5xl mx-auto px-4 py-3 w-full">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0 relative">
                        <button
                            v-if="showOwnerControls && lists && lists.length"
                            type="button"
                            class="flex items-center gap-1.5 text-lg font-bold text-body max-w-full"
                            @click="showListMenu = !showListMenu"
                        >
                            <span class="truncate">{{ listTitle }}</span>

                            <i
                                class="fa fa-chevron-down text-xs text-body-1/60 shrink-0"
                            />
                        </button>

                        <h1 v-else class="text-lg font-bold text-body truncate">
                            {{ listTitle }}
                        </h1>

                        <p
                            v-if="!inventoryMode"
                            class="text-xs text-body-1/70 mt-0.5"
                        >
                            {{
                                $t(
                                    "{buy} buying · {skip} skipped · {pending} pending",

                                    {
                                        buy: counts.buy,

                                        skip: counts.skip,

                                        pending: counts.pending,
                                    },
                                )
                            }}
                        </p>

                        <template v-if="inventoryMode">
                            <p class="mt-1 text-sm text-body-1">
                                Marca lo que ya hay en casa para omitirlo.
                            </p>

                            <p class="mt-2 text-xs font-medium text-body">
                                {{ guestCounts.needed }} faltan ·

                                {{ guestCounts.available }} hay
                            </p>
                        </template>

                        <template v-if="showListMenu">
                            <div
                                class="fixed inset-0 z-20"
                                @click="showListMenu = false"
                            />

                            <div
                                class="absolute left-0 top-9 z-30 w-64 max-w-[80vw] bg-base-lvl-3 border border-base rounded-xl shadow-2xl py-1.5"
                            >
                                <button
                                    v-for="l in lists"
                                    :key="l.id"
                                    type="button"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-base-lvl-2 transition"
                                    :class="
                                        l.id === plan.id
                                            ? 'text-primary font-semibold'
                                            : 'text-body'
                                    "
                                    @click="chooseList(l.id)"
                                >
                                    <span
                                        class="truncate flex items-center gap-1.5"
                                    >
                                        <i
                                            v-if="l.shared"
                                            class="fa fa-share-nodes text-[11px] text-body-1/50"
                                        />

                                        {{ l.name }}
                                    </span>

                                    <span
                                        class="text-[11px] text-body-1/50 shrink-0"
                                        >{{ l.pending }}</span
                                    >
                                </button>

                                <div class="my-1 border-t border-base" />

                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2 text-sm text-body hover:bg-base-lvl-2 transition"
                                    @click="promptCreate"
                                >
                                    <i class="fa fa-plus w-4 text-body-1/60" />

                                    {{ $t("New list") }}
                                </button>

                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2 text-sm text-body hover:bg-base-lvl-2 transition"
                                    @click="openImport"
                                >
                                    <i class="fa fa-paste w-4 text-body-1/60" />

                                    {{ $t("Import from text") }}
                                </button>

                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2 text-sm text-body hover:bg-base-lvl-2 transition"
                                    @click="promptRename"
                                >
                                    <i class="fa fa-pen w-4 text-body-1/60" />

                                    {{ $t("Rename this list") }}
                                </button>

                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2 text-sm text-error hover:bg-error/10 transition"
                                    @click="confirmDelete"
                                >
                                    <i class="fa fa-trash w-4" />

                                    {{ $t("Delete this list") }}
                                </button>
                            </div>
                        </template>
                    </div>

                    <div
                        v-if="inventoryMode"
                        class="flex shrink-0 items-center gap-1"
                    >
                        <button
                            type="button"
                            :aria-label="`Mostrar solo sin revisar (${uncheckedCount})`"
                            title="Sin revisar"
                            :aria-pressed="onlyUnchecked"
                            class="relative flex h-11 w-10 items-center justify-center rounded-full text-body hover:bg-base-lvl-2"
                            :class="{
                                'bg-primary/10 text-primary-ink': onlyUnchecked,
                            }"
                            @click="onlyUnchecked = !onlyUnchecked"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="h-5 w-5"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 5h16l-6 7v6l-4 2v-8L4 5Z"
                                />
                            </svg>
                            <span
                                v-if="uncheckedCount"
                                class="absolute right-0 top-0 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-white"
                                >{{ uncheckedCount }}</span
                            >
                        </button>
                        <button
                            type="button"
                            aria-label="Actualizar lista"
                            title="Actualizar lista"
                            :disabled="
                                reviewBusy ||
                                updatingItems.length > 0 ||
                                sending
                            "
                            class="h-11 w-10 rounded-full text-body hover:bg-base-lvl-2 disabled:opacity-50"
                            @click="refreshReview"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                class="mx-auto h-5 w-5"
                                :class="{ 'animate-spin': reviewBusy }"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M20 7v5h-5M4 17v-5h5M6.1 7a7 7 0 0 1 11.6-1L20 9M4 15l2.3 3A7 7 0 0 0 17.9 17"
                                />
                            </svg>
                        </button>

                        <button
                            type="button"
                            :disabled="
                                reviewBusy ||
                                updatingItems.length > 0 ||
                                sending
                            "
                            class="h-10 rounded-full bg-primaryDark px-3 text-sm font-semibold text-white disabled:opacity-50"
                            @click="openReview"
                        >
                            <i
                                class="fa fa-check mr-1"
                                aria-hidden="true"
                            />Confirmar lista
                        </button>
                    </div>

                    <div
                        v-if="showOwnerControls"
                        class="flex items-center gap-2 shrink-0"
                    >
                        <!-- Page-level secondary actions (e.g. Share) inject here -->

                        <slot name="header-actions" />

                        <button
                            v-if="!shoppingSession"
                            type="button"
                            :disabled="sessionBusy || updatingItems.length > 0"
                            class="rounded-full bg-primaryDark px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            @click="startShopping"
                        >
                            Iniciar compra
                        </button>
                        <button
                            v-else
                            type="button"
                            :disabled="sessionBusy || updatingItems.length > 0"
                            class="rounded-full bg-primaryDark px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            @click="openFinishShopping"
                        >
                            Terminar compra
                        </button>
                    </div>
                </div>
            </div>

            <p
                v-if="
                    inventoryMode && review?.status && review.status !== 'none'
                "
                role="status"
                class="mx-auto max-w-5xl px-4 pb-2 text-xs text-body-1/70"
            >
                <i class="fa fa-check mr-1" aria-hidden="true" />{{
                    review.status === "ready"
                        ? "Confirmada"
                        : review.status === "changed"
                          ? "Hay cambios sin confirmar"
                          : "Compra realizada"
                }}
                · {{ reviewDate }}
            </p>

            <p
                v-if="inventoryMode && reviewError && !reviewDialog?.open"
                role="alert"
                class="mx-auto max-w-5xl px-4 pb-2 text-sm text-error"
            >
                {{ reviewError }}
            </p>

            <p
                v-if="inventoryMode && confirmationMessage"
                role="status"
                class="mx-auto max-w-5xl px-4 pb-2 text-sm text-success"
            >
                {{ confirmationMessage }}
            </p>
            <p
                v-if="saveError"
                role="alert"
                class="mx-auto w-full max-w-5xl px-4 pb-3 text-sm text-error"
            >
                {{ saveError }}
            </p>
            <p
                v-if="!inventoryMode && shoppingSession"
                class="mx-auto max-w-5xl px-4 pb-2 text-sm text-body-1/70"
            >
                Modo compra · {{ shoppingSession.purchased_ids.length }} en el
                carrito de {{ shoppingSession.items.length }}
            </p>
            <p
                v-if="!inventoryMode && sessionMessage"
                role="status"
                class="mx-auto max-w-5xl px-4 pb-2 text-sm text-success"
            >
                {{ sessionMessage }}
            </p>
            <p
                v-if="!inventoryMode && sessionError && !finishDialog?.open"
                role="alert"
                class="mx-auto max-w-5xl px-4 pb-2 text-sm text-error"
            >
                {{ sessionError }}
            </p>
        </header>

        <div
            v-if="inventoryMode && (lastMark || undoError)"
            class="mx-auto flex w-full max-w-5xl flex-wrap items-center gap-2 px-4 pt-2"
        >
            <span
                v-if="lastMark"
                role="status"
                class="flex min-w-0 flex-1 items-center justify-end gap-2 text-xs text-body-1/70"
                ><span class="truncate">{{ lastMark.title }}</span
                ><button
                    type="button"
                    :disabled="undoBusy || updatingItems.length > 0"
                    class="shrink-0 rounded-lg px-2 py-2 font-semibold text-primary disabled:opacity-50"
                    @click="undoMark"
                >
                    Deshacer
                </button></span
            >
            <p v-if="undoError" role="alert" class="w-full text-xs text-error">
                {{ undoError }}
            </p>
        </div>
        <!-- Item list — scrolls behind the sticky composer -->

        <main class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto px-3 py-3 w-full space-y-5">
                <details
                    v-if="endpoints.review && !inventoryMode"
                    :open="false"
                    class="rounded-xl border border-base bg-base-lvl-2 text-body"
                >
                    <summary
                        class="cursor-pointer px-4 py-3 text-sm font-semibold"
                    >
                        {{ reviewTitle }}
                        <span
                            v-if="reviewDate"
                            class="font-normal text-xs text-body-1/60"
                        >
                            · {{ reviewDate }}</span
                        >
                    </summary>

                    <div class="flex flex-col gap-3 px-4 pb-4">
                        <template v-if="inventoryMode">
                            <label class="text-sm" for="purchase-note"
                                >Nota para esta compra
                                <span class="text-body-1/60"
                                    >(opcional)</span
                                ></label
                            >

                            <textarea
                                id="purchase-note"
                                v-model="reviewNote"
                                maxlength="2000"
                                rows="2"
                                placeholder="Talla, marca o alguna indicación para esta visita…"
                                class="w-full rounded-lg border border-base bg-base-lvl-1 p-3 text-sm text-body"
                            />

                            <p class="text-xs text-body-1/70">
                                Confirma cuando hayas revisado qué hace falta.
                                Avisaremos a quien va a comprar.
                            </p>
                        </template>

                        <template v-else>
                            <p
                                v-if="review?.status === 'none'"
                                class="text-sm text-body-1/60"
                            >
                                Aún no se ha confirmado esta compra.
                            </p>

                            <p
                                v-if="review?.status === 'changed'"
                                class="text-sm text-warning"
                            >
                                La lista cambió después de revisarla. Comprueba
                                los productos antes de comprar.
                            </p>

                            <p
                                v-if="
                                    review?.note &&
                                    review.status !== 'completed'
                                "
                                class="whitespace-pre-wrap text-sm"
                            >
                                {{ review.note }}
                            </p>
                        </template>

                        <p
                            v-if="reviewError"
                            role="alert"
                            class="text-sm text-error"
                        >
                            {{ reviewError }}
                        </p>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                :disabled="
                                    reviewBusy ||
                                    updatingItems.length > 0 ||
                                    sending
                                "
                                class="rounded-lg border border-base px-3 py-2 text-sm disabled:opacity-50"
                                @click="refreshReview"
                            >
                                Actualizar
                            </button>
                        </div>
                    </div>
                </details>

                <div
                    v-if="allItems.length === 0"
                    class="flex flex-col items-center justify-center text-center text-body-1/60 py-16 px-6"
                >
                    <div class="text-4xl mb-2">🛒</div>

                    <p class="text-sm">{{ $t("Nothing here yet.") }}</p>

                    <p class="text-xs text-body-1/40 mt-1">
                        {{ $t("Type an item below and hit send.") }}
                    </p>
                </div>

                <p
                    v-if="
                        inventoryMode &&
                        allItems.length &&
                        !visibleSections.length
                    "
                    class="px-1 py-6 text-sm text-body-1/70"
                >
                    {{
                        onlyUnchecked
                            ? "No quedan coincidencias sin revisar."
                            : "No hay productos con ese nombre."
                    }}
                </p>

                <section
                    v-for="section in visibleSections"
                    :key="section.id"
                    v-show="
                        section.items.length ||
                        (!inventoryMode && hasCategories)
                    "
                >
                    <div
                        v-if="hasCategories"
                        class="flex items-center justify-between px-1 mb-2"
                    >
                        <template
                            v-if="
                                renamingStageId === section.id &&
                                showOwnerControls
                            "
                        >
                            <input
                                ref="renameInput"
                                v-model="renamingName"
                                class="flex-1 text-[11px] font-bold uppercase tracking-wide bg-transparent border-b border-primary/50 outline-none text-body-1 px-0 py-0.5"
                                @keyup.enter="
                                    commitRename(
                                        stages.find(
                                            (s) => s.id === section.id,
                                        )!,
                                    )
                                "
                                @keyup.escape="cancelRename"
                                @blur="
                                    commitRename(
                                        stages.find(
                                            (s) => s.id === section.id,
                                        )!,
                                    )
                                "
                                @vue:mounted="
                                    ($event) =>
                                        ($event.el as HTMLInputElement).focus()
                                "
                            />
                        </template>

                        <template v-else>
                            <h3
                                class="text-sm font-semibold text-body-1/80"
                                :class="{
                                    'cursor-pointer hover:text-body-1 transition':
                                        showOwnerControls,
                                }"
                                @dblclick="
                                    showOwnerControls &&
                                    startRename(
                                        stages.find(
                                            (s) => s.id === section.id,
                                        )!,
                                    )
                                "
                            >
                                {{ section.name }}
                            </h3>
                        </template>

                        <div
                            v-if="
                                showOwnerControls &&
                                renamingStageId !== section.id
                            "
                            class="flex items-center gap-1"
                        >
                            <button
                                type="button"
                                class="text-body-1/30 hover:text-body transition px-1"
                                :title="$t('Rename category')"
                                @click="
                                    startRename(
                                        stages.find(
                                            (s) => s.id === section.id,
                                        )!,
                                    )
                                "
                            >
                                <i class="fa fa-pen text-[10px]" />
                            </button>

                            <button
                                v-if="stages.length > 1"
                                type="button"
                                class="text-body-1/30 hover:text-error transition px-1"
                                :title="$t('Delete category')"
                                @click="
                                    confirmDeleteStageId =
                                        confirmDeleteStageId === section.id
                                            ? null
                                            : section.id
                                "
                            >
                                <i class="fa fa-trash text-[10px]" />
                            </button>
                        </div>
                    </div>

                    <!-- Inline delete confirmation -->

                    <div
                        v-if="confirmDeleteStageId === section.id"
                        class="flex items-center gap-2 px-3 py-2 mb-2 rounded-lg bg-error/10 text-sm"
                    >
                        <span class="flex-1 text-body-1">
                            {{
                                $t(
                                    'Delete "{name}"? Items move to the first category.',

                                    { name: section.name },
                                )
                            }}
                        </span>

                        <button
                            type="button"
                            class="px-2.5 py-1 rounded text-xs font-medium bg-error text-white hover:bg-error/80 transition"
                            @click="
                                deleteCategory(
                                    stages.find((s) => s.id === section.id)!,
                                )
                            "
                        >
                            {{ $t("Delete") }}
                        </button>

                        <button
                            type="button"
                            class="px-2.5 py-1 rounded text-xs font-medium text-body-1/70 hover:text-body transition"
                            @click="confirmDeleteStageId = null"
                        >
                            {{ $t("Cancel") }}
                        </button>
                    </div>

                    <div
                        class="space-y-2 md:space-y-0 md:grid md:grid-cols-2 xl:grid-cols-3 md:gap-2"
                    >
                        <div
                            v-for="item in section.items"
                            :key="item.id"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl border transition cursor-pointer select-none"
                            :class="
                                inventoryMode
                                    ? 'bg-base-lvl-3 border-base'
                                    : stateClass(item.state)
                            "
                            :role="inventoryMode ? 'group' : undefined"
                            :aria-label="inventoryMode ? item.title : undefined"
                            @click="!inventoryMode && cycleItem(item)"
                        >
                            <i
                                v-if="!inventoryMode"
                                class="fa text-base shrink-0"
                                :class="stateIcon(item.state)"
                            />

                            <span class="min-w-0 flex-1 text-sm break-words"
                                >{{ item.title
                                }}<small
                                    v-if="review?.item_details?.[item.id]"
                                    class="mt-1 block whitespace-pre-wrap text-xs text-body-1/70"
                                    >{{
                                        [
                                            review.item_details[item.id]
                                                .quantity,
                                            review.item_details[item.id].note,
                                        ]
                                            .filter(Boolean)
                                            .join(" · ")
                                    }}</small
                                ><small
                                    v-if="
                                        inventoryMode &&
                                        !isReviewed(item) &&
                                        !updatingItems.includes(item.id)
                                    "
                                    class="mt-1 block text-xs text-body-1/70"
                                    >Sin revisar</small
                                ><small
                                    v-if="
                                        inventoryMode &&
                                        updatingItems.includes(item.id)
                                    "
                                    role="status"
                                    class="mt-1 block text-xs text-body-1/70"
                                    >Guardando…</small
                                ></span
                            >

                            <span
                                v-if="!inventoryMode && item.state === 'buy'"
                                class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide bg-success/20 text-success"
                            >
                                {{ $t("Buying") }}
                            </span>

                            <span
                                v-else-if="
                                    !inventoryMode && item.state === 'skip'
                                "
                                class="shrink-0 px-2 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide bg-error/15 text-error/70"
                            >
                                {{ $t("Skip") }}
                            </span>

                            <div
                                v-if="inventoryMode"
                                class="flex shrink-0 gap-1"
                                @click.stop
                            >
                                <button
                                    type="button"
                                    class="min-h-[44px] min-w-[52px] rounded-lg border px-2 text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50"
                                    :class="
                                        isReviewed(item) &&
                                        item.state !== 'pending'
                                            ? 'border-red-600 bg-red-50 text-red-900 dark:border-red-400 dark:bg-red-900/20 dark:text-red-200'
                                            : 'border-base text-body-1/80'
                                    "
                                    :aria-pressed="
                                        isReviewed(item) &&
                                        item.state !== 'pending'
                                    "
                                    :aria-label="'Ya hay: ' + item.title"
                                    @click="cycleItem(item, true)"
                                >
                                    <span
                                        v-if="
                                            isReviewed(item) &&
                                            item.state !== 'pending'
                                        "
                                        aria-hidden="true"
                                        >✕ </span
                                    >Ya hay
                                </button>

                                <button
                                    type="button"
                                    class="min-h-[44px] min-w-[52px] rounded-lg border px-2 text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50"
                                    :class="
                                        isReviewed(item) &&
                                        item.state === 'pending'
                                            ? 'border-slate-500 bg-slate-100 text-slate-900 dark:border-slate-400 dark:bg-slate-800 dark:text-slate-100'
                                            : 'border-base text-body-1/80'
                                    "
                                    :aria-pressed="
                                        isReviewed(item) &&
                                        item.state === 'pending'
                                    "
                                    :aria-label="'Hace falta: ' + item.title"
                                    @click="cycleItem(item, false)"
                                >
                                    <span
                                        v-if="
                                            isReviewed(item) &&
                                            item.state === 'pending'
                                        "
                                        aria-hidden="true"
                                        >✓ </span
                                    >Hace falta
                                </button>
                            </div>

                            <button
                                v-if="inventoryMode"
                                type="button"
                                class="flex h-11 w-7 shrink-0 items-center justify-center rounded-lg text-body-1/60 hover:bg-base-lvl-2"
                                :aria-label="'Editar detalles: ' + item.title"
                                @click.stop="openMoveCategory(item, section.id)"
                            >
                                <span aria-hidden="true" class="text-xl"
                                    >⋮</span
                                >
                            </button>
                            <button
                                v-if="endpoints.destroy"
                                type="button"
                                class="text-body-1/30 hover:text-error transition shrink-0 px-1"
                                :title="$t('Remove item')"
                                @click.stop="removeItem(item)"
                            >
                                <i class="fa fa-trash text-xs" />
                            </button>
                        </div>
                    </div>
                </section>

                <div v-if="showOwnerControls && !shoppingSession">
                    <div
                        v-if="isAddingCategory"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg border border-primary/30 bg-base-lvl-2"
                    >
                        <input
                            v-model="newCategoryName"
                            class="flex-1 text-sm bg-transparent outline-none text-body placeholder:text-body-1/40"
                            :placeholder="$t('Category name')"
                            @keyup.enter="commitAddCategory"
                            @keyup.escape="isAddingCategory = false"
                            @blur="commitAddCategory"
                            @vue:mounted="
                                ($event) =>
                                    ($event.el as HTMLInputElement).focus()
                            "
                        />
                    </div>

                    <button
                        v-else
                        type="button"
                        class="w-full flex items-center justify-center gap-1.5 py-2 text-xs text-body-1/50 hover:text-body transition rounded-lg border border-dashed border-base-lvl-1 hover:border-base"
                        @click="startAddCategory"
                    >
                        <i class="fa fa-plus" />

                        {{ $t("New category") }}
                    </button>
                </div>
            </div>
        </main>

        <!-- Sticky composer at the bottom — WhatsApp-style. The keyboard pushes

             this up naturally on iOS/Android because it's flex-positioned, not

             fixed. -->

        <footer class="border-t border-base bg-base-lvl-3 pb-safe">
            <div
                v-if="inventoryMode && composer.trim() && !existingProduct"
                class="mx-auto flex max-w-5xl flex-wrap items-center gap-2 px-4 pt-3"
            >
                <label for="product-category" class="text-xs text-body-1/70"
                    >Categoría</label
                >
                <NSelect
                    v-model:value="productCategory"
                    :options="categoryOptions"
                    :disabled="sending"
                    size="small"
                    class="min-w-0 flex-1"
                />
                <input
                    v-if="productCategory === 'new'"
                    v-model="productCategoryName"
                    aria-label="Nombre de la nueva categoría"
                    placeholder="Nombre de la categoría"
                    maxlength="120"
                    class="w-full rounded-lg border border-base bg-base-lvl-2 px-3 py-2 text-sm text-body"
                    :disabled="sending"
                />
            </div>
            <div
                v-if="inventoryMode && composer.trim()"
                role="status"
                class="mx-auto flex max-w-5xl items-center justify-between gap-2 px-4 pt-2 text-xs text-body-1/70"
            >
                <span>{{
                    existingProduct
                        ? "Ya está en la lista. Puedes cambiar su disponibilidad arriba."
                        : `Agregar “${composer.trim()}” como producto nuevo`
                }}</span>
                <button
                    type="button"
                    class="shrink-0 rounded-lg px-2 py-2 text-body"
                    @click="composer = ''"
                >
                    Limpiar
                </button>
            </div>

            <form
                class="max-w-5xl mx-auto px-3 py-2 flex items-center gap-2 w-full"
                @submit.prevent="submitComposer"
            >
                <button
                    v-if="inventoryMode"
                    type="button"
                    aria-label="Agregar nota para esta compra"
                    title="Agregar nota"
                    class="relative h-11 w-10 shrink-0 rounded-full text-body-1 hover:bg-base-lvl-2"
                    @click="openReview"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="mx-auto h-5 w-5"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10l6-6V5a2 2 0 0 0-2-2h-5ZM15 21v-6h6M7 8h10M7 12h6"
                        /></svg
                    ><span
                        v-if="reviewNote.trim()"
                        class="absolute right-1 top-1 h-2 w-2 rounded-full bg-primary"
                    />
                </button>

                <button
                    v-if="!inventoryMode"
                    type="button"
                    aria-label="Ver historial de compras"
                    title="Historial de compras"
                    class="flex h-11 w-10 shrink-0 items-center justify-center rounded-full text-body-1 hover:bg-base-lvl-2"
                    @click="openHistory"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 4v5h5M3.6 9a9 9 0 1 1-.2 6M12 7v5l3 2"
                        />
                    </svg>
                </button>
                <input
                    v-model="composer"
                    type="text"
                    :placeholder="
                        inventoryMode
                            ? 'Buscar o agregar producto…'
                            : $t('Add an item…')
                    "
                    :aria-label="
                        inventoryMode
                            ? 'Buscar o agregar producto'
                            : $t('Add an item…')
                    "
                    class="min-w-0 flex-1 px-3 py-2.5 text-sm rounded-full bg-base-lvl-2 border border-base text-body focus:border-primary focus:outline-none"
                    autocomplete="off"
                    enterkeyhint="send"
                />

                <button
                    type="submit"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-primary text-white shadow-md transition disabled:opacity-50"
                    :disabled="!composer.trim() || sending || !!existingProduct"
                    :title="inventoryMode ? 'Agregar producto' : $t('Send')"
                    :aria-label="
                        inventoryMode ? 'Agregar producto' : $t('Send')
                    "
                >
                    <i
                        :class="
                            inventoryMode
                                ? 'fa fa-plus text-sm'
                                : 'fa fa-paper-plane text-sm'
                        "
                    />
                </button>
            </form>
        </footer>

        <Teleport to="body">
            <dialog
                ref="historyDialog"
                aria-labelledby="shopping-history-title"
                class="w-[calc(100%-1.5rem)] max-w-lg max-h-[85dvh] flex-col overflow-hidden rounded-2xl border border-base bg-base-lvl-3 p-0 text-body shadow-2xl backdrop:bg-black/60 open:flex"
                @click="
                    $event.target === historyDialog && historyDialog?.close()
                "
            >
                <div
                    class="flex shrink-0 items-center justify-between gap-2 border-b border-base bg-base-lvl-3 px-4 py-3"
                >
                    <button
                        v-if="selectedVisit"
                        type="button"
                        aria-label="Volver al historial"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full hover:bg-base-lvl-2"
                        @click="backToHistory"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-5 w-5"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m14 6-6 6 6 6M8 12h12"
                            />
                        </svg>
                    </button>
                    <h2
                        id="shopping-history-title"
                        class="min-w-0 flex-1 truncate text-sm font-semibold sm:text-base"
                    >
                        {{
                            selectedVisit
                                ? "Detalle de la visita"
                                : "Historial de compras"
                        }}
                    </h2>
                    <Transition name="visit-header">
                        <span
                            v-if="selectedVisit && headerShowsAmount"
                            class="flex shrink-0 items-baseline gap-1 text-right tabular-nums"
                            ><span class="text-[10px] text-body-1/60">{{
                                selectedVisit.currency
                            }}</span
                            ><span class="text-sm font-semibold">{{
                                formatVisitAmount(selectedVisit.total)
                            }}</span></span
                        >
                    </Transition>
                    <button
                        type="button"
                        aria-label="Cerrar historial"
                        class="h-10 w-10 rounded-full text-2xl hover:bg-base-lvl-2"
                        @click="historyDialog?.close()"
                    >
                        &times;
                    </button>
                </div>
                <div
                    class="visit-scroll min-h-0 overflow-y-auto overscroll-contain"
                    @scroll.passive="handleHistoryScroll"
                >
                    <div
                        v-if="selectedVisit"
                        class="flex flex-col gap-3 px-4 pb-4 pt-3"
                    >
                        <div
                            class="flex flex-col gap-1.5 border-b border-base pb-3"
                        >
                            <div
                                class="flex items-baseline justify-between gap-3"
                            >
                                <p
                                    ref="visitAmountElement"
                                    class="flex items-baseline gap-1.5 tabular-nums"
                                >
                                    <span class="text-xs text-body-1/60">{{
                                        selectedVisit.currency
                                    }}</span
                                    ><span
                                        class="text-2xl font-semibold tracking-tight"
                                        >{{
                                            formatVisitAmount(
                                                selectedVisit.total,
                                            )
                                        }}</span
                                    >
                                </p>
                                <span class="text-xs text-success"
                                    >Terminada</span
                                >
                            </div>
                        </div>
                        <section
                            v-if="selectedVisit.note"
                            class="flex flex-col gap-1 border-l-2 border-primary/50 pl-2.5"
                        >
                            <h3 class="text-xs font-medium text-body-1/60">
                                Nota de la visita
                            </h3>
                            <p
                                class="whitespace-pre-wrap break-words text-sm leading-snug"
                            >
                                {{ selectedVisit.note }}
                            </p>
                        </section>
                        <section class="flex flex-col gap-2">
                            <h3 class="text-sm font-semibold">
                                Productos comprados
                            </h3>
                            <div v-for="group in visitGroups" :key="group.name">
                                <div
                                    class="flex items-center justify-between gap-2 border-b border-base py-1"
                                >
                                    <h4
                                        class="text-xs font-medium text-body-1/70"
                                    >
                                        {{ group.name }}
                                    </h4>
                                    <span
                                        class="text-xs tabular-nums text-body-1/50"
                                        >{{ group.items.length }}</span
                                    >
                                </div>
                                <ul class="divide-y divide-base">
                                    <li
                                        v-for="item in group.items"
                                        :key="item.id"
                                        class="flex items-center gap-2 py-1.5 text-sm"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-success/70"
                                            aria-hidden="true"
                                        /><span class="break-words">{{
                                            item.title
                                        }}</span>
                                    </li>
                                </ul>
                            </div>
                        </section>
                        <section
                            v-if="selectedVisit.remaining_items.length"
                            class="flex flex-col gap-1.5 border-t border-dashed border-base pt-2"
                        >
                            <h3
                                class="flex items-center justify-between text-sm font-medium text-body-1/70"
                            >
                                Quedaron pendientes
                                <span class="text-xs tabular-nums">{{
                                    selectedVisit.remaining_items.length
                                }}</span>
                            </h3>
                            <ul
                                class="flex flex-col gap-1.5 text-sm text-body-1/60"
                            >
                                <li
                                    v-for="item in selectedVisit.remaining_items"
                                    :key="item.id"
                                    class="flex items-center gap-3"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full border border-current"
                                        aria-hidden="true"
                                    />{{ item.title }}
                                </li>
                            </ul>
                        </section>
                    </div>
                    <template v-else>
                        <p
                            v-if="!visits.length"
                            class="px-4 py-8 text-center text-sm text-body-1/70"
                        >
                            Las visitas aparecerán aquí cuando termines una
                            compra.
                        </p>
                        <p v-else class="px-5 pb-1 pt-3 text-xs text-body-1/60">
                            {{ visits.length }}
                            {{
                                visits.length === 1
                                    ? "visita guardada"
                                    : "visitas guardadas"
                            }}
                        </p>
                        <div class="divide-y divide-base px-3 pb-2">
                            <button
                                v-for="visit in visits"
                                :key="visit.id"
                                type="button"
                                class="flex min-h-[52px] w-full items-center justify-between gap-3 rounded-lg px-2 py-2 text-left hover:bg-base-lvl-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                @click="viewVisit(visit)"
                            >
                                <span
                                    class="flex min-w-0 flex-1 flex-col gap-0.5"
                                >
                                    <span
                                        class="flex items-baseline justify-between gap-3"
                                        ><span
                                            class="text-sm font-semibold tabular-nums"
                                            >{{ visit.currency }}
                                            {{
                                                formatVisitAmount(visit.total)
                                            }}</span
                                        ><span class="text-xs text-body-1/60"
                                            >{{
                                                visit.purchased_items.length
                                            }}
                                            productos</span
                                        ></span
                                    >
                                    <span class="text-xs text-body-1/60"
                                        >{{
                                            new Date(
                                                visit.completed_at,
                                            ).toLocaleDateString("es-DO", {
                                                day: "numeric",
                                                month: "short",
                                                year: "numeric",
                                            })
                                        }}
                                        ·
                                        {{
                                            formatVisitTime(visit.completed_at)
                                        }}</span
                                    >
                                </span>
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-5 w-5 shrink-0 text-body-1/60"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m9 6 6 6-6 6"
                                    />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
                <footer
                    v-if="selectedVisit"
                    aria-label="Resumen de la visita"
                    class="flex shrink-0 items-center justify-between gap-3 border-t border-base bg-base-lvl-2 px-4 py-2.5"
                >
                    <p class="text-xs text-body-1/60">
                        {{
                            new Date(
                                selectedVisit.completed_at,
                            ).toLocaleDateString("es-DO", {
                                day: "numeric",
                                month: "short",
                                year: "numeric",
                            })
                        }}
                        ·
                        {{ formatVisitTime(selectedVisit.completed_at) }}
                    </p>
                    <p class="text-right text-xs text-body-1/60">
                        {{ selectedVisit.purchased_items.length }}
                        comprados<span
                            v-if="selectedVisit.remaining_items.length"
                        >
                            ·
                            {{ selectedVisit.remaining_items.length }}
                            pendientes</span
                        >
                    </p>
                </footer>
            </dialog>
            <dialog
                ref="finishDialog"
                aria-labelledby="finish-shopping-title"
                class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-base bg-base-lvl-3 p-5 text-body shadow-xl backdrop:bg-black/50"
                @cancel="sessionBusy && $event.preventDefault()"
            >
                <form
                    class="flex flex-col gap-4"
                    @submit.prevent="finishShopping"
                >
                    <h2
                        id="finish-shopping-title"
                        class="text-lg font-semibold"
                    >
                        Terminar compra
                    </h2>
                    <p class="text-sm text-body-1/70">
                        {{ shoppingSession?.purchased_ids.length ?? 0 }}
                        productos comprados. Los demás quedarán pendientes.
                    </p>
                    <p class="text-xs text-body-1/70">
                        Se buscará un movimiento en tu tarjeta con el mismo monto
                        y categoría para vincularlo automáticamente. Si pagaste
                        en efectivo, se registrará como gasto directo.
                    </p>
                    <label for="visit-total" class="text-sm"
                        >Importe total</label
                    >
                    <div class="flex gap-2">
                        <select
                            v-model="visitCurrency"
                            aria-label="Moneda de la compra"
                            class="rounded-lg border border-base bg-base-lvl-2 p-3"
                        >
                            <option>DOP</option>
                            <option>USD</option>
                            <option>EUR</option></select
                        ><input
                            id="visit-total"
                            v-model="visitTotal"
                            type="number"
                            min="0"
                            max="999999999.99"
                            step="0.01"
                            required
                            inputmode="decimal"
                            placeholder="0.00"
                            class="min-w-0 flex-1 rounded-lg border border-base bg-base-lvl-2 p-3"
                        />
                    </div>
                    <label for="visit-note" class="text-sm"
                        >Notas de esta visita
                        <span class="text-body-1/70">(opcional)</span></label
                    >
                    <textarea
                        id="visit-note"
                        v-model="visitNote"
                        rows="3"
                        maxlength="2000"
                        class="rounded-lg border border-base bg-base-lvl-2 p-3"
                        placeholder="Algo que quieras recordar de esta compra…"
                    />
                    <p
                        v-if="sessionError"
                        role="alert"
                        class="text-sm text-error"
                    >
                        {{ sessionError }}
                    </p>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            :disabled="sessionBusy"
                            class="rounded-lg px-4 py-3"
                            @click="finishDialog?.close()"
                        >
                            Volver</button
                        ><button
                            type="submit"
                            :disabled="
                                sessionBusy ||
                                !shoppingSession?.purchased_ids.length
                            "
                            class="rounded-lg bg-primaryDark px-4 py-3 font-semibold text-white disabled:opacity-50"
                        >
                            {{ sessionBusy ? "Guardando…" : "Guardar visita" }}
                        </button>
                    </div>
                </form>
            </dialog>
            <dialog
                ref="categoryDialog"
                aria-labelledby="move-category-title"
                class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-base bg-base-lvl-3 p-5 text-body shadow-xl backdrop:bg-black/50"
                @cancel="categoryBusy && $event.preventDefault()"
            >
                <form
                    class="flex flex-col gap-4"
                    @submit.prevent="saveMoveCategory"
                >
                    <h2 id="move-category-title" class="text-lg font-semibold">
                        Detalles del producto
                    </h2>
                    <p class="text-sm text-body-1/70">
                        {{ movingItem?.title }}
                    </p>
                    <label for="move-category" class="text-sm">Categoría</label>
                    <NSelect
                        v-model:value="movingCategory"
                        :options="moveCategoryOptions"
                        :disabled="categoryBusy"
                        size="medium"
                    />
                    <label for="item-quantity" class="text-sm"
                        >Cantidad
                        <span class="text-body-1/70">(opcional)</span></label
                    >
                    <input
                        id="item-quantity"
                        v-model="itemQuantity"
                        maxlength="60"
                        placeholder="Ej. 2 paquetes"
                        :disabled="categoryBusy"
                        class="rounded-lg border border-base bg-base-lvl-2 p-3 text-body"
                    />
                    <label for="item-note" class="text-sm"
                        >Indicación para esta compra
                        <span class="text-body-1/70">(opcional)</span></label
                    >
                    <textarea
                        id="item-note"
                        v-model="itemNote"
                        maxlength="500"
                        rows="2"
                        placeholder="Marca, talla o alguna preferencia…"
                        :disabled="categoryBusy"
                        class="rounded-lg border border-base bg-base-lvl-2 p-3 text-body"
                    />
                    <p
                        v-if="categoryError"
                        role="alert"
                        class="text-sm text-error"
                    >
                        {{ categoryError }}
                    </p>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            :disabled="categoryBusy"
                            class="rounded-lg px-4 py-3"
                            @click="categoryDialog?.close()"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="categoryBusy"
                            class="rounded-lg bg-primary px-4 py-3 font-semibold text-white disabled:opacity-50"
                        >
                            {{ categoryBusy ? "Guardando…" : "Guardar" }}
                        </button>
                    </div>
                </form>
            </dialog>
            <dialog
                ref="reviewDialog"
                aria-labelledby="review-dialog-title"
                class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-base bg-base-lvl-3 p-0 text-body shadow-xl backdrop:bg-black/50"
                @click="
                    $event.target === reviewDialog &&
                    !reviewBusy &&
                    reviewDialog?.close()
                "
                @cancel="reviewBusy && $event.preventDefault()"
            >
                <form
                    class="flex flex-col gap-4 p-5"
                    @submit.prevent="submitReview"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2
                            id="review-dialog-title"
                            class="text-lg font-semibold"
                        >
                            ¿Todo listo para comprar?
                        </h2>

                        <button
                            type="button"
                            aria-label="Cerrar nota"
                            :disabled="reviewBusy"
                            class="h-10 w-10 rounded-full hover:bg-base-lvl-2 disabled:opacity-50"
                            @click="reviewDialog?.close()"
                        >
                            <span class="text-2xl" aria-hidden="true"
                                >&times;</span
                            >
                        </button>
                    </div>

                    <label for="purchase-confirmation-note" class="text-sm"
                        >Nota para esta compra
                        <span class="text-body-1/70">(opcional)</span></label
                    >

                    <textarea
                        id="purchase-confirmation-note"
                        v-model="reviewNote"
                        autofocus
                        maxlength="2000"
                        rows="3"
                        placeholder="Una marca, talla o indicación para esta visita…"
                        class="w-full rounded-xl border border-base bg-base-lvl-1 p-3 text-sm text-body focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />

                    <p v-if="uncheckedCount" class="text-sm text-warning">
                        Quedan {{ uncheckedCount }} productos sin revisar.
                        Puedes volver para comprobarlos antes de confirmar.
                    </p>
                    <p class="text-sm text-body-1/70">
                        Al confirmar, avisaremos a quien va a comprar que ya puede
                        comprar.
                    </p>

                    <p
                        v-if="reviewError"
                        role="alert"
                        class="text-sm text-error"
                    >
                        {{ reviewError }}
                    </p>

                    <button
                        type="submit"
                        :disabled="
                            reviewBusy || updatingItems.length > 0 || sending
                        "
                        class="rounded-xl bg-primaryDark px-4 py-3 font-semibold text-white disabled:opacity-50"
                    >
                        {{ reviewBusy ? "Confirmando…" : "Confirmar lista" }}
                    </button>
                </form>
            </dialog>

            <div
                v-if="showImport"
                class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/50"
                @click.self="showImport = false"
            >
                <div
                    class="w-full max-w-lg bg-base-lvl-3 border border-base rounded-2xl shadow-2xl p-4"
                >
                    <h3 class="font-bold text-body mb-1">
                        {{ $t("Import a list") }}
                    </h3>

                    <p class="text-xs text-body-1/60 mb-3">
                        {{
                            $t(
                                "Paste a list. Lines starting with a dash are items; other lines become categories.",
                            )
                        }}
                    </p>

                    <input
                        v-model="importName"
                        type="text"
                        :placeholder="$t('List name (optional)')"
                        class="w-full mb-2 px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body focus:border-primary focus:outline-none"
                    />

                    <textarea
                        v-model="importText"
                        rows="10"
                        placeholder="🛒 Compra para Diana&#10;🥩 Carnes&#10;- Pechuga cruda&#10;- Huevo"
                        class="w-full px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body focus:border-primary focus:outline-none"
                    />

                    <div class="flex items-center justify-end gap-2 mt-3">
                        <button
                            type="button"
                            class="text-sm px-3 py-2 rounded-lg text-body-1 hover:bg-base-lvl-2 transition"
                            @click="showImport = false"
                        >
                            {{ $t("Cancel") }}
                        </button>

                        <button
                            type="button"
                            class="text-sm px-4 py-2 rounded-lg font-semibold bg-primary text-white disabled:opacity-50 transition"
                            :disabled="!importText.trim()"
                            @click="submitImport"
                        >
                            {{ $t("Import") }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.visit-header-enter-active,
.visit-header-leave-active {
    transition:
        opacity 150ms ease,
        transform 150ms ease;
}
.visit-header-enter-from {
    opacity: 0;
    transform: translateY(4px);
}
.visit-header-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
@media (prefers-reduced-motion: reduce) {
    .visit-header-enter-active,
    .visit-header-leave-active {
        transition: none;
    }
}

.visit-scroll {
    scrollbar-width: auto;
    scrollbar-color: rgb(128 139 153 / 28%) transparent;
}
.visit-scroll:hover {
    scrollbar-color: rgb(128 139 153 / 48%) transparent;
}
@supports selector(::-webkit-scrollbar) {
    .visit-scroll,
    .visit-scroll:hover {
        scrollbar-color: auto;
    }
}
.visit-scroll::-webkit-scrollbar {
    width: 4px;
}
.visit-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.visit-scroll::-webkit-scrollbar-thumb {
    background: rgb(128 139 153 / 28%);
    border-radius: 999px;
}
.visit-scroll:hover::-webkit-scrollbar-thumb {
    background: rgb(128 139 153 / 48%);
}
.visit-scroll::-webkit-scrollbar-button,
.visit-scroll::-webkit-scrollbar-button:single-button,
.visit-scroll::-webkit-scrollbar-button:vertical:decrement,
.visit-scroll::-webkit-scrollbar-button:vertical:increment {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
    background: transparent !important;
}

.pb-safe {
    padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
}
</style>
