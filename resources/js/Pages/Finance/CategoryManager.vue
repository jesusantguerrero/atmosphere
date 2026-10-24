<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { NSelect } from "naive-ui";
import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { formatMoney } from "@/utils";

type Category = {
    id: number;
    name: string;
    parent_id: number | null;
    display_id: string;
    protected: boolean;
    target?: string;
    archived?: boolean;
};
const props = defineProps<{ managedCategories: Category[]; merges: any[] }>();
const search = ref("");
const showAutomatic = ref(false);
const goalTypes: Record<string, string> = {
    spending: "Gasto",
    saving_balance: "Ahorro con objetivo",
    savings_monthly: "Ahorro mensual",
    loan: "Préstamo",
    debt_monthly_payment: "Pago mensual de deuda",
    debt_payoff_date: "Liquidar deuda",
    challenge_under_amount: "Límite de gasto",
};
const selectedId = ref<number | null>(null);
const editor = ref<HTMLElement | null>(null);
const selectCategory = async (id: number) => {
    selectedId.value = id;
    await nextTick();
    if (window.matchMedia("(max-width: 1023px)").matches) {
        editor.value?.scrollIntoView({ block: "start" });
    }
};
const selected = computed(() =>
    props.managedCategories.find((item) => item.id === selectedId.value),
);
const groups = computed(() =>
    props.managedCategories.filter((item) => !item.parent_id && !item.archived),
);
const visibleGroups = computed(() =>
    groups.value
        .filter(
            (group) =>
                showAutomatic.value ||
                !group.protected ||
                group.display_id === "hidden_categories",
        )
        .map((group) => ({
            ...group,
            children: props.managedCategories.filter(
                (item) =>
                    item.parent_id === group.id &&
                    (!search.value ||
                        item.name
                            .toLocaleLowerCase()
                            .includes(search.value.toLocaleLowerCase()) ||
                        group.name
                            .toLocaleLowerCase()
                            .includes(search.value.toLocaleLowerCase())),
            ),
        }))
        .filter(
            (group) =>
                group.children.length ||
                group.name
                    .toLocaleLowerCase()
                    .includes(search.value.toLocaleLowerCase()),
        ),
);
const groupOptions = computed(() =>
    groups.value
        .filter(
            (item) =>
                !item.protected || item.display_id === "hidden_categories",
        )
        .map((item) => ({ label: item.name, value: item.id })),
);
const targetOptions = computed(() =>
    props.managedCategories
        .filter(
            (item) =>
                item.parent_id &&
                !item.protected &&
                item.id !== selectedId.value,
        )
        .map((item) => ({
            label: `${groups.value.find((group) => group.id === item.parent_id)?.name} · ${item.name}`,
            value: item.id,
        })),
);
const name = ref("");
const parentId = ref<number | null>(null);
const targetId = ref<number | null>(null);
const preview = ref<any>(null);
const busy = ref(false);
const error = ref("");
const notice = ref("");
const creating = ref(false);
const createGroup = ref(false);
const confirmed = ref(false);
const goalChoice = ref<string | null>(null);
const previewBlockers = computed(() =>
    (preview.value?.blockers ?? []).filter(
        (message: string) =>
            !(
                goalChoice.value &&
                message.startsWith("Ambas categorías tienen metas.")
            ),
    ),
);
watch(
    selected,
    () => {
        name.value = selected.value?.name ?? "";
        parentId.value = selected.value?.parent_id ?? null;
        targetId.value = null;
        preview.value = null;
        error.value = "";
        creating.value = false;
    },
    { flush: "sync" },
);
watch(targetId, () => {
    preview.value = null;
    confirmed.value = false;
    goalChoice.value = null;
});
watch(goalChoice, () => {
    confirmed.value = false;
});
const request = async (work: () => Promise<any>, message = "") => {
    if (busy.value) return;
    busy.value = true;
    error.value = "";
    notice.value = "";
    try {
        const result = await work();
        if (message && result !== false) {
            notice.value = message;
            router.reload({
                only: ["managedCategories", "merges", "categories"],
                preserveScroll: true,
            });
        }
    } catch (failure: any) {
        error.value =
            Object.values(failure.response?.data?.errors ?? {})
                .flat()
                .join(" ") ||
            failure.response?.data?.message ||
            "No se pudo guardar. Inténtalo de nuevo.";
    } finally {
        busy.value = false;
    }
};
const startCreate = (isGroup: boolean) => {
    selectedId.value = null;
    creating.value = true;
    createGroup.value = isGroup;
    name.value = "";
    parentId.value = groupOptions.value[0]?.value ?? null;
    error.value = "";
};
const save = () =>
    request(async () => {
        if (creating.value) {
            const { data } = await axios.post(route("category-manager.store"), {
                name: name.value,
                parent_id: createGroup.value ? null : parentId.value,
            });
            creating.value = false;
            selectedId.value = data.id;
        } else {
            await axios.patch(
                route("category-manager.update", selectedId.value),
                {
                    name: name.value,
                    ...(selected.value?.parent_id
                        ? { parent_id: parentId.value }
                        : {}),
                },
            );
        }
    }, "Cambios guardados.");
const showPreview = () =>
    request(async () => {
        const source = selectedId.value;
        const target = targetId.value;
        const { data } = await axios.post(
            route("category-manager.preview", source),
            { target_id: target },
        );
        if (selectedId.value !== source || targetId.value !== target) return;
        preview.value = data;
        confirmed.value = false;
        goalChoice.value = null;
    });
const merge = () =>
    request(async () => {
        if (!confirmed.value || !preview.value || previewBlockers.value.length)
            return false;
        await axios.post(route("category-manager.merge", selectedId.value), {
            target_id: targetId.value,
            token: preview.value.token,
            goal: goalChoice.value,
        });
        selectedId.value = targetId.value;
        preview.value = null;
    }, "Categorías unificadas. El historial se conserva y puedes revisar la operación abajo.");
const archive = () =>
    request(
        () =>
            axios.patch(route("category-manager.update", selectedId.value), {
                archive: true,
            }),
        "Categoría movida a categorías ocultas. Conserva todo su historial; puedes volver a moverla a otro grupo.",
    );
const undo = (id: number) =>
    request(
        () => axios.post(route("category-manager.undo", id)),
        "Unificación deshecha. Se restauraron las categorías y sus datos originales.",
    );
const archiveGroup = () =>
    request(
        () =>
            axios.patch(route("category-manager.update", selectedId.value), {
                archive: true,
            }),
        "Grupo archivado. Puedes restaurarlo al final de la lista.",
    );
const restoreGroup = (id: number) =>
    request(
        () =>
            axios.patch(route("category-manager.update", id), {
                archive: false,
            }),
        "Grupo restaurado.",
    );
</script>

<template>
    <AppLayout
        title="Categorías"
        :show-back-button="true"
        @back="router.visit(route('budgets.index'))"
    >
        <template #header><FinanceSectionNav /></template>
        <main class="mx-auto mt-12 w-full max-w-6xl p-4 md:p-6 text-body-1">
            <header
                class="mb-6 flex flex-wrap items-start justify-between gap-4"
            >
                <div>
                    <h1 class="text-2xl font-semibold">
                        Organiza tu presupuesto
                    </h1>
                    <p class="mt-1 text-sm text-body-1/60">
                        Agrupa, renombra y unifica categorías conservando su
                        historial.
                    </p>
                </div>
                <div class="flex gap-2">
                    <LogerButton @click="startCreate(true)"
                        >Nuevo grupo</LogerButton
                    ><LogerButton variant="inverse" @click="startCreate(false)"
                        >Nueva categoría</LogerButton
                    >
                </div>
            </header>
            <p
                v-if="notice"
                role="status"
                class="mb-4 rounded-lg border border-success/30 bg-success/10 p-3 text-sm"
            >
                {{ notice }}
            </p>
            <p
                v-if="error"
                role="alert"
                class="mb-4 rounded-lg border border-error/30 bg-error/10 p-3 text-sm"
            >
                {{ error }}
            </p>
            <div
                class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
            >
                <section
                    class="min-w-0 overflow-hidden rounded-xl border border-body-1/10 bg-base-lvl-1"
                    :class="selected || creating ? 'hidden lg:block' : ''"
                >
                    <div
                        class="flex flex-col gap-3 border-b border-body-1/10 p-3"
                    >
                        <input
                            v-model="search"
                            aria-label="Buscar categorías"
                            placeholder="Buscar categorías o grupos…"
                            class="w-full rounded-lg border border-body-1/15 bg-base px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                        /><label
                            class="flex items-center gap-2 text-xs text-body-1/60"
                            ><input
                                v-model="showAutomatic"
                                type="checkbox"
                                class="rounded text-primary focus:ring-primary"
                            />Mostrar categorías automáticas</label
                        >
                    </div>
                    <div class="max-h-[65vh] overflow-y-auto">
                        <details
                            v-if="
                                managedCategories.some((item) => item.archived)
                            "
                            class="border-b border-body-1/10 p-4 text-sm"
                        >
                            <summary class="cursor-pointer text-body-1/60">
                                Grupos archivados
                            </summary>
                            <div
                                v-for="group in managedCategories.filter(
                                    (item) => item.archived,
                                )"
                                :key="group.id"
                                class="mt-3 flex items-center justify-between gap-3"
                            >
                                <span>{{ group.name }}</span
                                ><button
                                    :disabled="busy"
                                    class="text-primary"
                                    @click="restoreGroup(group.id)"
                                >
                                    Restaurar
                                </button>
                            </div>
                        </details>
                        <section v-for="group in visibleGroups" :key="group.id">
                            <button
                                class="flex w-full items-center justify-between gap-3 bg-base-lvl-2/50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide"
                                :class="
                                    selectedId === group.id
                                        ? 'text-primary'
                                        : 'text-body-1/60'
                                "
                                @click="selectCategory(group.id)"
                            >
                                <span>{{ group.name }}</span
                                ><span>{{ group.children.length }}</span>
                            </button>
                            <button
                                v-for="category in group.children"
                                :key="category.id"
                                class="flex min-h-[52px] w-full items-center justify-between gap-3 border-b border-body-1/5 px-4 py-2 text-left text-sm transition hover:bg-base-lvl-2 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary"
                                :class="
                                    selectedId === category.id
                                        ? 'bg-primary/10 text-primary-ink'
                                        : ''
                                "
                                :aria-pressed="selectedId === category.id"
                                @click="selectCategory(category.id)"
                            >
                                <span class="min-w-0"
                                    ><span
                                        class="block break-words font-medium"
                                        >{{ category.name }}</span
                                    ><span
                                        v-if="category.target"
                                        class="text-xs text-body-1/50"
                                        >Con meta</span
                                    ></span
                                ><span
                                    v-if="category.protected"
                                    class="shrink-0 text-xs text-body-1/50"
                                    >Automática</span
                                ><span
                                    v-else
                                    aria-hidden="true"
                                    class="text-body-1/40"
                                    >›</span
                                >
                            </button>
                        </section>
                        <p
                            v-if="!visibleGroups.length"
                            class="p-6 text-sm text-body-1/60"
                        >
                            No se encontraron categorías.
                        </p>
                    </div>
                </section>
                <section
                    ref="editor"
                    class="min-w-0 scroll-mt-28 rounded-xl border border-body-1/10 bg-base-lvl-1 p-5 lg:sticky lg:top-4"
                    :class="!selected && !creating ? 'hidden lg:block' : ''"
                >
                    <template v-if="creating || selected">
                        <button
                            class="mb-4 text-sm text-primary lg:hidden"
                            @click="
                                selectedId = null;
                                creating = false;
                            "
                        >
                            ← Volver a categorías
                        </button>
                        <h2 class="text-lg font-semibold">
                            {{
                                creating
                                    ? createGroup
                                        ? "Nuevo grupo"
                                        : "Nueva categoría"
                                    : selected?.name
                            }}
                        </h2>
                        <p
                            v-if="selected?.protected"
                            class="mt-3 text-sm text-body-1/60"
                        >
                            Esta categoría es automática o tiene una función
                            predeterminada. Se conserva para proteger el
                            funcionamiento del presupuesto.
                        </p>
                        <template v-else>
                            <p
                                v-if="selected?.archived"
                                class="mt-3 text-sm text-body-1/60"
                            >
                                Este grupo está archivado. Puedes restaurarlo
                                desde la lista de grupos archivados.
                            </p>
                            <form
                                v-if="!selected?.archived"
                                class="mt-4 flex flex-col gap-4"
                                @submit.prevent="save"
                            >
                                <label class="flex flex-col gap-1.5 text-sm"
                                    >Nombre<input
                                        v-model="name"
                                        required
                                        maxlength="255"
                                        class="rounded-lg border border-body-1/15 bg-base px-3 py-2 focus:border-primary focus:ring-primary"
                                /></label>
                                <label
                                    v-if="
                                        creating
                                            ? !createGroup
                                            : selected?.parent_id
                                    "
                                    class="flex flex-col gap-1.5 text-sm"
                                    >Grupo<NSelect
                                        v-model:value="parentId"
                                        :options="groupOptions"
                                        filterable
                                        placeholder="Elige un grupo"
                                /></label>
                                <div class="flex flex-wrap items-center gap-3">
                                    <LogerButton
                                        type="submit"
                                        variant="inverse"
                                        :disabled="busy || !name.trim()"
                                        >{{
                                            busy
                                                ? "Guardando…"
                                                : "Guardar cambios"
                                        }}</LogerButton
                                    ><button
                                        v-if="
                                            !creating &&
                                            selected?.parent_id &&
                                            groups.find(
                                                (item) =>
                                                    item.id ===
                                                    selected?.parent_id,
                                            )?.display_id !==
                                                'hidden_categories'
                                        "
                                        type="button"
                                        :disabled="busy"
                                        class="text-sm text-body-1/60 underline underline-offset-4"
                                        @click="archive"
                                    >
                                        Ocultar sin borrar
                                    </button>
                                </div>
                            </form>
                            <div
                                v-if="
                                    !creating &&
                                    selected &&
                                    !selected.parent_id &&
                                    !selected.archived
                                "
                                class="mt-5 border-t border-body-1/10 pt-4"
                            >
                                <p class="mb-3 text-sm text-body-1/60">
                                    Para retirar este grupo, mueve primero todas
                                    sus categorías. Podrás restaurar el grupo
                                    después.
                                </p>
                                <LogerButton
                                    :disabled="
                                        busy ||
                                        managedCategories.some(
                                            (item) =>
                                                item.parent_id === selectedId,
                                        )
                                    "
                                    @click="archiveGroup"
                                    >Archivar grupo vacío</LogerButton
                                >
                            </div>
                            <section
                                v-if="!creating && selected?.parent_id"
                                class="mt-6 border-t border-body-1/10 pt-5"
                            >
                                <h3 class="font-semibold">
                                    Unificar con otra categoría
                                </h3>
                                <p class="mt-1 text-sm text-body-1/60">
                                    Elige cuál conservarás. Sus transacciones y
                                    asignaciones se reunirán sin cambiar los
                                    saldos de tus cuentas.
                                </p>
                                <label
                                    class="mt-4 flex flex-col gap-1.5 text-sm"
                                    >Categoría que conservarás<NSelect
                                        v-model:value="targetId"
                                        :options="targetOptions"
                                        filterable
                                        placeholder="Buscar categoría de destino"
                                /></label>
                                <LogerButton
                                    class="mt-3"
                                    :disabled="busy || !targetId"
                                    @click="showPreview"
                                    >Revisar unificación</LogerButton
                                >
                                <div
                                    v-if="preview"
                                    class="mt-4 rounded-lg border border-body-1/15 bg-base p-4 text-sm"
                                >
                                    <p class="break-words font-semibold">
                                        {{ preview.source }} →
                                        {{ preview.target }}
                                    </p>
                                    <p class="mt-2 text-body-1/70">
                                        {{ preview.transactions }} transacciones
                                        · {{ preview.lines }} partidas ·
                                        {{ preview.months }} meses de
                                        presupuesto se trasladarán.
                                    </p>
                                    <p class="mt-2 text-body-1/60">
                                        Se incluyen todos los períodos, las
                                        transacciones divididas y los
                                        movimientos entre categorías. La
                                        categoría de origen quedará en el
                                        historial de unificaciones.
                                    </p>
                                    <details
                                        v-if="preview.periods.length"
                                        class="mt-3"
                                    >
                                        <summary
                                            class="cursor-pointer font-medium"
                                        >
                                            Ver asignaciones combinadas por mes
                                        </summary>
                                        <div
                                            class="mt-2 max-h-48 overflow-y-auto"
                                        >
                                            <div
                                                v-for="period in preview.periods"
                                                :key="period.month"
                                                class="flex justify-between gap-2 py-1 tabular-nums"
                                            >
                                                <span>{{
                                                    period.month.slice(0, 7)
                                                }}</span
                                                ><span>{{
                                                    formatMoney(
                                                        Number(period.budgeted),
                                                        period.currency ||
                                                            "DOP",
                                                    )
                                                }}</span>
                                            </div>
                                        </div>
                                    </details>
                                    <fieldset
                                        v-if="preview.goals.length === 2"
                                        class="mt-4 flex flex-col gap-2 rounded-lg border border-body-1/15 p-3"
                                    >
                                        <legend class="px-1 font-semibold">
                                            ¿Qué meta conservarás?
                                        </legend>
                                        <label
                                            v-for="goal in preview.goals"
                                            :key="goal.side"
                                            class="flex items-start gap-2"
                                            ><input
                                                v-model="goalChoice"
                                                type="radio"
                                                :value="goal.side"
                                                class="mt-0.5 text-primary focus:ring-primary"
                                            /><span
                                                >{{ goal.name }} ·
                                                {{ goal.amount }}</span
                                            ></label
                                        >
                                        <p class="text-xs text-body-1/60">
                                            La otra meta dejará de estar activa
                                            y su configuración original se
                                            guardará en el historial de esta
                                            unificación. No se sumarán los
                                            objetivos.
                                        </p>
                                    </fieldset>
                                    <p
                                        v-for="blocker in previewBlockers"
                                        :key="blocker"
                                        class="mt-3 text-warning"
                                    >
                                        {{ blocker }}
                                    </p>
                                    <template v-if="!previewBlockers.length"
                                        ><label
                                            class="mt-4 flex items-start gap-2"
                                            ><input
                                                v-model="confirmed"
                                                type="checkbox"
                                                class="mt-0.5 rounded text-primary focus:ring-primary"
                                            /><span
                                                >He revisado la vista previa y
                                                quiero conservar
                                                {{ preview.target }}.</span
                                            ></label
                                        ><LogerButton
                                            variant="inverse"
                                            class="mt-3"
                                            :disabled="busy || !confirmed"
                                            @click="merge"
                                            >Confirmar unificación</LogerButton
                                        ></template
                                    >
                                </div>
                            </section>
                        </template>
                    </template>
                    <div v-else class="py-10 text-center">
                        <h2 class="font-semibold">
                            Cada categoría, en su lugar
                        </h2>
                        <p class="mx-auto mt-2 max-w-xs text-sm text-body-1/60">
                            Selecciona una categoría o un grupo para
                            organizarlo. Antes de unificar, podrás revisar qué
                            cambiará.
                        </p>
                    </div>
                </section>
            </div>
            <section
                v-if="merges.length"
                class="mt-6 rounded-xl border border-body-1/10 bg-base-lvl-1 p-5"
            >
                <h2 class="font-semibold">Historial de unificaciones</h2>
                <p class="mt-1 text-sm text-body-1/60">
                    Puedes deshacer si no hubo cambios posteriores en los datos
                    afectados. Así se protegen tus nuevas ediciones.
                </p>
                <div
                    v-for="operation in merges"
                    :key="operation.id"
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-body-1/10 py-3 last:border-0"
                >
                    <div class="min-w-0 text-sm">
                        <p class="break-words">
                            {{ operation.source_name }} →
                            {{ operation.target_name }}
                        </p>
                        <p class="mt-1 text-xs text-body-1/50">
                            {{
                                new Date(operation.created_at).toLocaleString(
                                    "es",
                                )
                            }}
                            ·
                            {{
                                operation.undone_at ? "Deshecha" : "Completada"
                            }}
                        </p>
                        <details
                            v-if="operation.goals.length"
                            class="mt-2 text-xs text-body-1/60"
                        >
                            <summary class="cursor-pointer">
                                Metas originales conservadas
                            </summary>
                            <p
                                v-for="(goal, index) in operation.goals"
                                :key="index"
                                class="mt-1"
                            >
                                {{ goal.name }} · {{ goal.amount }} ·
                                {{ goalTypes[goal.type] || "Meta" }}
                            </p>
                        </details>
                    </div>
                    <LogerButton
                        v-if="!operation.undone_at"
                        :disabled="busy"
                        @click="undo(operation.id)"
                        >Deshacer</LogerButton
                    >
                </div>
            </section>
        </main>
    </AppLayout>
</template>
