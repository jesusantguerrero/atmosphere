<script setup lang="ts">
import { ref, watch } from "vue";
import axios from "axios";
import { Link } from "@inertiajs/vue3";
const props = defineProps<{ taskId: number | string }>();
const emit = defineEmits<{ (event: "saved"): void }>();
const profiles = ref<{ id: number; name: string }[]>([]);
const selected = ref<number[]>([]);
const editing = ref(false);
const busy = ref(false);
const error = ref("");
let requestNumber = 0;
watch(
    () => props.taskId,
    async (id) => {
        const current = ++requestNumber;
        editing.value = false;
        profiles.value = [];
        selected.value = [];
        error.value = "";
        if (!id) return;
        busy.value = true;
        try {
            const { data } = await axios.get(
                route("profile-task-links.show", { itemId: id }),
            );
            if (current !== requestNumber) return;
            profiles.value = data.profiles;
            selected.value = data.profile_ids.map(Number);
        } catch {
            if (current === requestNumber)
                error.value =
                    "No se pudieron cargar los perfiles relacionados.";
        } finally {
            if (current === requestNumber) busy.value = false;
        }
    },
    { immediate: true },
);
const save = async () => {
    busy.value = true;
    error.value = "";
    try {
        await axios.put(
            route("profile-task-links.update", { itemId: props.taskId }),
            { profile_ids: selected.value },
        );
        editing.value = false;
        emit("saved");
    } catch {
        error.value = "No se pudo guardar la vinculación. Inténtalo de nuevo.";
    } finally {
        busy.value = false;
    }
};
</script>
<template>
    <section
        class="my-4 border-t border-body-1/15 pt-4"
        aria-label="Perfiles relacionados"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold">Perfiles relacionados</h3>
            <button
                type="button"
                class="min-h-[44px] rounded px-2 text-sm text-primary focus-visible:ring-2 focus-visible:ring-primary"
                :disabled="busy"
                @click="editing = !editing"
            >
                {{ editing ? "Cerrar" : "Vincular perfiles" }}
            </button>
        </div>
        <p class="mb-2 text-xs text-body-1/75">
            Indica con quién se relaciona esta tarea. No cambia su responsable.
        </p>
        <p v-if="busy" role="status" class="text-sm">Cargando…</p>
        <p v-if="error" role="alert" class="text-sm text-danger">{{ error }}</p>
        <div
            v-if="editing"
            class="flex max-h-64 flex-col gap-1 overflow-y-auto"
        >
            <label
                v-for="profile in profiles"
                :key="profile.id"
                class="flex min-h-[44px] items-center gap-2 text-sm"
                ><input
                    v-model="selected"
                    type="checkbox"
                    :value="profile.id"
                    :disabled="busy"
                />{{ profile.name }}</label
            ><button
                type="button"
                :disabled="busy"
                class="min-h-[44px] self-start rounded bg-primary px-3 text-white focus-visible:ring-2 focus-visible:ring-primary"
                @click="save"
            >
                Guardar vínculos
            </button>
        </div>
        <div v-else class="flex flex-wrap gap-2">
            <Link
                v-for="profile in profiles.filter((item) =>
                    selected.includes(item.id),
                )"
                :key="profile.id"
                :href="route('loger-profiles.show', { profileId: profile.id })"
                class="rounded bg-primary/10 px-2 py-1 text-sm text-primary-ink underline focus-visible:ring-2 focus-visible:ring-primary"
                >{{ profile.name }}</Link
            ><span
                v-if="!busy && !selected.length"
                class="text-sm text-body-1/75"
                >Sin perfiles relacionados</span
            >
        </div>
    </section>
</template>
