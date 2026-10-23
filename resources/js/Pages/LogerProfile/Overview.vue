<script setup lang="ts">
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Components/templates/AppLayout.vue";
import ProfilesPillarNav from "@/Components/templates/ProfilesPillarNav.vue";
import Glyph from "@/domains/logerProfile/ProfileGlyph.vue";
import type { ProfilePage } from "@/domains/logerProfile/profilePreview";
type ProfileSummary = {
    id: number;
    name: string;
    personal_user_id?: number;
    url: string;
    pending: ProfilePage[];
    upcoming: ProfilePage[];
};
const props = defineProps<{ profiles: ProfileSummary[] }>();
const pendingCount = computed(() =>
    props.profiles.reduce(
        (total, profile) => total + profile.pending.length,
        0,
    ),
);
const upcoming = computed(() =>
    props.profiles
        .flatMap((profile) =>
            profile.upcoming.map((event) => ({ ...event, profile })),
        )
        .sort((a, b) => (a.when ?? "").localeCompare(b.when ?? ""))
        .slice(0, 8),
);
const dateLabel = (date?: string) =>
    date
        ? new Date(date).toLocaleString("es-DO", {
              dateStyle: "medium",
              timeStyle: "short",
          })
        : "";
</script>
<template>
    <AppLayout :title="$t('Profiles')">
        <template #header><ProfilesPillarNav /></template>
        <main
            class="mx-auto mt-12 w-full max-w-7xl px-4 pb-12 pt-6 sm:px-6 lg:px-8"
        >
            <header
                class="mb-8 flex flex-wrap items-center justify-between gap-4"
            >
                <div>
                    <h1 class="text-2xl font-semibold text-body-1">Perfiles</h1>
                    <p class="mt-2 text-sm text-body-1/60">
                        Lo próximo y lo pendiente, reunido por perfil.
                    </p>
                </div>
                <Link
                    :href="route('loger-profiles.index')"
                    class="rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white"
                    >Ver perfiles</Link
                >
            </header>
            <div
                v-if="!profiles.length"
                class="rounded-xl border border-dashed border-body-1/20 p-8 text-center text-body-1/60"
            >
                <p>
                    Añade tu primer perfil para reunir sus cuadernos, tareas y
                    seguimientos.
                </p>
                <Link
                    :href="route('loger-profiles.index')"
                    class="mt-4 inline-block text-primary"
                    >Crear un perfil</Link
                >
            </div>
            <div v-else class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-body-1">Por perfil</h2>
                        <span class="text-xs text-body-1/60"
                            >{{ pendingCount }} pendientes</span
                        >
                    </div>
                    <div class="grid gap-4 xl:grid-cols-2">
                        <Link
                            v-for="profile in profiles"
                            :key="profile.id"
                            :href="profile.url"
                            class="rounded-xl border border-body-1/10 bg-base-lvl-3 p-5 transition hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            <div class="mb-4 flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-lg text-primary"
                                    >{{
                                        profile.name.charAt(0).toUpperCase()
                                    }}</span
                                >
                                <h3
                                    class="break-words font-semibold text-body-1"
                                >
                                    {{
                                        Number(profile.personal_user_id) ===
                                        Number($page.props.auth.user.id)
                                            ? "Yo"
                                            : profile.name
                                    }}
                                    <span
                                        v-if="
                                            Number(profile.personal_user_id) ===
                                            Number($page.props.auth.user.id)
                                        "
                                        class="ml-1 rounded bg-primary/10 px-1.5 py-0.5 text-xs text-primary"
                                        >Tú</span
                                    >
                                </h3>
                                <Glyph
                                    name="arrow"
                                    class="ml-auto !h-4 !w-4 text-body-1/40"
                                />
                            </div>
                            <p class="mb-3 text-xs text-body-1/60">
                                {{ profile.pending.length }} pendientes ·
                                {{ profile.upcoming.length }} próximos eventos
                            </p>
                            <ul
                                v-if="profile.pending.length"
                                class="flex flex-col gap-2"
                            >
                                <li
                                    v-for="task in profile.pending.slice(0, 3)"
                                    :key="task.id"
                                    class="flex items-start gap-2 text-sm text-body-1/80"
                                >
                                    <Glyph
                                        name="check"
                                        class="!h-4 !w-4 text-body-1/40"
                                    />{{ task.title }}
                                </li>
                            </ul>
                            <p v-else class="text-sm text-body-1/50">
                                Sin tareas pendientes.
                            </p>
                        </Link>
                    </div>
                </section>
                <aside>
                    <h2 class="mb-4 font-semibold text-body-1">Lo próximo</h2>
                    <div class="flex flex-col gap-3">
                        <Link
                            v-for="event in upcoming"
                            :key="event.profile.id + ':' + event.id"
                            :href="event.profile.url"
                            class="rounded-xl border border-body-1/10 p-4 hover:border-primary/40"
                            ><p class="text-xs text-primary">
                                {{ dateLabel(event.when) }}
                            </p>
                            <h3 class="mt-2 text-sm font-medium text-body-1">
                                {{ event.title }}
                            </h3>
                            <p class="mt-1 text-xs text-body-1/60">
                                {{ event.profile.name }}
                            </p></Link
                        >
                        <p
                            v-if="!upcoming.length"
                            class="text-sm text-body-1/50"
                        >
                            No hay próximos eventos en los perfiles vinculados.
                        </p>
                    </div>
                </aside>
            </div>
        </main>
    </AppLayout>
</template>
