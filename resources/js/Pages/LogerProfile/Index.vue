<script lang="ts" setup>
import { computed, ref, onMounted, onBeforeUnmount, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ProfilesPillarNav from "@/Components/templates/ProfilesPillarNav.vue";
import LogerProfileModal from "@/Components/LogerProfileModal.vue";
import Glyph from "@/domains/logerProfile/ProfileGlyph.vue";
type Profile = { id: number; name: string; personal_user_id?: number };
const props = defineProps<{
    profiles: {
        data: Profile[];
        total: number;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    profileCount: number;
    filters: { search: string; sort: string };
}>();
const page = usePage();
const isModalOpen = ref(false);
const resourceToEdit = ref({});
const toolsVisible = ref(false);
const search = ref(props.filters.search);
const sort = ref(props.filters.sort);
const loading = ref(false);
const error = ref("");
const remoteCollection = computed(() => props.profileCount > 24);
const showTools = computed(
    () => props.profileCount >= 8 || !!props.filters.search,
);
const isMe = (profile: Profile) =>
    Number(profile.personal_user_id) ===
    Number((page.props.auth as any).user.id);
const visibleProfiles = computed(() => {
    if (remoteCollection.value) return props.profiles.data;
    return [...props.profiles.data]
        .filter((profile) =>
            `${profile.name} ${isMe(profile) ? "Yo" : ""}`
                .toLocaleLowerCase()
                .includes(search.value.trim().toLocaleLowerCase()),
        )
        .sort(
            (a, b) =>
                Number(isMe(b)) - Number(isMe(a)) ||
                (sort.value === "name_desc" ? -1 : 1) *
                    a.name.localeCompare(b.name) ||
                a.id - b.id,
        );
});
watch(
    () => props.filters,
    (value) => {
        search.value = value.search;
        sort.value = value.sort;
    },
);
const visit = (url?: string | null) => {
    error.value = "";
    router.get(
        url || route("loger-profiles.index"),
        url
            ? {}
            : {
                  search: search.value.trim() || undefined,
                  sort: sort.value === "name_desc" ? sort.value : undefined,
              },
        {
            preserveState: true,
            preserveScroll: true,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
            onError: () => {
                error.value =
                    "No se pudieron cargar los perfiles. Inténtalo de nuevo.";
            },
        },
    );
};
const searchProfiles = () => {
    if (remoteCollection.value) visit();
};
const clearSearch = () => {
    search.value = "";
    if (remoteCollection.value || props.filters.search) visit();
};
let removeExceptionListener: (() => void) | undefined;
onMounted(() => {
    removeExceptionListener = router.on("exception", (event) => {
        if (!loading.value) return;
        event.preventDefault();
        loading.value = false;
        error.value =
            "No se pudieron cargar los perfiles. Revisa la conexión e inténtalo de nuevo.";
    });
});
onBeforeUnmount(() => removeExceptionListener?.());
// Deterministic per-profile avatar color, drawn from the app's chart palette
const AVATAR_PALETTE = [
    "#7B77D1", // purple
    "#F37EA1", // pink
    "#80CDFE", // blue
    "#6EE7B7", // teal-green
    "#FBBF77", // amber
    "#A78BFA", // violet
    "#5EEAD4", // teal
    "#F59E9E", // coral
];

const avatarStyle = (profile: Record<string, any>) => {
    const seed = String(profile.id ?? profile.name ?? "");
    let hash = 0;
    for (let i = 0; i < seed.length; i++) {
        hash = (hash * 31 + seed.charCodeAt(i)) >>> 0;
    }
    const color = AVATAR_PALETTE[hash % AVATAR_PALETTE.length];
    return { backgroundColor: `${color}1F`, color };
};

const onSaved = () => router.reload();
</script>

<template>
    <AppLayout :title="$t('Profiles')" :hide-tools="!toolsVisible">
        <template #header><ProfilesPillarNav /></template>
        <main
            class="mx-auto w-full max-w-[1400px] px-4 pb-12 pt-20 text-body-1 sm:px-6 lg:px-8"
        >
            <header
                class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-body-1/15 pb-5"
            >
                <div>
                    <h1
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        Perfiles
                    </h1>
                    <p class="mt-1 text-sm text-body-1/75">
                        {{ profileCount }}
                        {{ profileCount === 1 ? "perfil" : "perfiles" }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex h-11 w-11 items-center justify-center rounded-lg border border-body-1/15 text-body-1/80 hover:bg-body-1/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        :aria-pressed="toolsVisible"
                        :aria-label="
                            toolsVisible
                                ? 'Ocultar herramientas'
                                : 'Mostrar herramientas'
                        "
                        :title="
                            toolsVisible
                                ? 'Ocultar herramientas'
                                : 'Mostrar herramientas'
                        "
                        @click="toolsVisible = !toolsVisible"
                    >
                        <Glyph name="sidebar" />
                    </button>
                    <LogerButton variant="inverse" @click="isModalOpen = true"
                        ><Glyph name="plus" class="mr-2" />Agregar
                        perfil</LogerButton
                    >
                </div>
            </header>
            <form
                v-if="showTools"
                class="mb-6 flex flex-wrap items-end gap-3"
                role="search"
                @submit.prevent="searchProfiles"
            >
                <label class="flex w-full flex-col gap-2 text-sm sm:w-80"
                    >Buscar perfiles<input
                        v-model="search"
                        type="search"
                        maxlength="120"
                        placeholder="Buscar perfiles…"
                        :disabled="loading"
                        class="min-h-[44px] rounded-lg border border-body-1/20 bg-base-lvl-3 px-3 text-body-1 placeholder:text-body-1/65 focus:border-primary focus:ring-primary"
                /></label>
                <label class="flex flex-col gap-2 text-sm"
                    >Orden<select
                        v-model="sort"
                        :disabled="loading"
                        class="min-h-[44px] rounded-lg border border-body-1/20 bg-base-lvl-3 pl-3 pr-9 text-body-1 focus:border-primary focus:ring-primary"
                        @change="searchProfiles"
                    >
                        <option value="name">Nombre: A–Z</option>
                        <option value="name_desc">Nombre: Z–A</option>
                    </select></label
                >
                <button
                    v-if="remoteCollection"
                    type="submit"
                    :disabled="loading"
                    class="min-h-[44px] rounded-lg bg-primary px-4 font-medium text-white focus-visible:ring-2 focus-visible:ring-primary"
                >
                    Buscar
                </button>
                <button
                    v-if="search || filters.search"
                    type="button"
                    :disabled="loading"
                    class="min-h-[44px] rounded-lg px-3 text-primary hover:bg-primary/10 focus-visible:ring-2 focus-visible:ring-primary"
                    @click="clearSearch"
                >
                    Limpiar búsqueda
                </button>
            </form>
            <div
                v-if="error"
                role="alert"
                class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-danger/30 bg-danger/10 p-4"
            >
                <p>{{ error }}</p>
                <button
                    type="button"
                    class="min-h-[44px] px-3 underline focus-visible:ring-2 focus-visible:ring-primary"
                    @click="visit()"
                >
                    Reintentar
                </button>
            </div>
            <section
                aria-label="Colección de perfiles"
                :aria-busy="loading"
                class="relative min-h-[160px]"
            >
                <p
                    v-if="loading"
                    role="status"
                    class="absolute -top-5 text-xs text-body-1/75"
                >
                    Cargando perfiles…
                </p>
                <div
                    v-if="visibleProfiles.length"
                    class="profile-collection-grid"
                    :class="loading ? 'pointer-events-none opacity-60' : ''"
                >
                    <Link
                        v-for="profile in visibleProfiles"
                        :key="profile.id"
                        :href="
                            route('loger-profiles.show', {
                                profileId: profile.id,
                            })
                        "
                        :title="profile.name"
                        class="group flex min-h-[88px] min-w-0 items-center gap-3 rounded-xl border border-body-1/15 bg-base-lvl-3 p-4 text-left transition-colors hover:border-primary/60 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-base"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full text-lg font-semibold"
                            :style="avatarStyle(profile)"
                        >
                            <img
                                v-if="
                                    isMe(profile) &&
                                    ($page.props.auth.user as any)
                                        .profile_photo_url
                                "
                                :src="
                                    ($page.props.auth.user as any)
                                        .profile_photo_url
                                "
                                alt=""
                                class="h-full w-full object-cover"
                            />
                            <span v-else aria-hidden="true">{{
                                profile.name.charAt(0).toUpperCase()
                            }}</span>
                        </div>
                        <div class="min-w-0">
                            <h2
                                class="line-clamp-2 break-words text-sm font-semibold leading-5 text-body-1"
                            >
                                {{ isMe(profile) ? "Yo" : profile.name }}
                            </h2>
                            <span
                                v-if="isMe(profile)"
                                class="mt-1 inline-block text-xs font-medium text-primary"
                                >Tú</span
                            >
                        </div>
                    </Link>
                </div>
                <div
                    v-else-if="profileCount && (search || filters.search)"
                    class="flex flex-col items-start gap-3 rounded-xl border border-body-1/15 bg-base-lvl-3 p-6"
                >
                    <h2 class="font-semibold">No encontramos perfiles</h2>
                    <p class="text-sm text-body-1/75">
                        No hay coincidencias para «{{
                            search || filters.search
                        }}».
                    </p>
                    <button
                        class="min-h-[44px] rounded-lg px-3 text-primary hover:bg-primary/10 focus-visible:ring-2 focus-visible:ring-primary"
                        @click="clearSearch"
                    >
                        Limpiar búsqueda
                    </button>
                </div>
                <div
                    v-else
                    class="flex max-w-lg flex-col items-start gap-3 rounded-xl border border-body-1/15 bg-base-lvl-3 p-6"
                >
                    <h2 class="font-semibold">Tus perfiles, en un lugar</h2>
                    <p class="text-sm text-body-1/75">
                        Crea un perfil para reunir sus cuadernos y la
                        información que quieras vincular.
                    </p>
                    <LogerButton variant="inverse" @click="isModalOpen = true"
                        >Agregar perfil</LogerButton
                    >
                </div>
            </section>
            <nav
                v-if="profiles.last_page > 1"
                aria-label="Páginas de perfiles"
                class="mt-6 flex flex-wrap items-center gap-3 text-sm"
            >
                <button
                    :disabled="!profiles.prev_page_url || loading"
                    class="min-h-[44px] rounded-lg border border-body-1/20 px-4 disabled:opacity-40 focus-visible:ring-2 focus-visible:ring-primary"
                    @click="visit(profiles.prev_page_url)"
                >
                    Anterior</button
                ><span role="status" class="text-body-1/80"
                    >Página {{ profiles.current_page }} de
                    {{ profiles.last_page }} ·
                    {{ profiles.total }} resultados</span
                ><button
                    :disabled="!profiles.next_page_url || loading"
                    class="min-h-[44px] rounded-lg border border-body-1/20 px-4 disabled:opacity-40 focus-visible:ring-2 focus-visible:ring-primary"
                    @click="visit(profiles.next_page_url)"
                >
                    Siguiente
                </button>
            </nav>
        </main>
        <LogerProfileModal
            v-if="isModalOpen"
            v-model:show="isModalOpen"
            :form-data="resourceToEdit"
            @saved="onSaved"
        />
    </AppLayout>
</template>

<style scoped>
.profile-collection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 220px), 280px));
    gap: 1rem;
}
@media (max-width: 479px) {
    .profile-collection-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
