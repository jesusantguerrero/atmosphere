<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

import DialogModal from '@/Components/atoms/DialogModal.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import LogerInput from '@/Components/atoms/LogerInput.vue';

interface Row {
    date: string;
    name: string;
    total: string;
    notes: string;
}

const props = defineProps<{ show: boolean }>();
const emit = defineEmits<{ (e: 'close'): void }>();

const today = () => new Date().toISOString().slice(0, 10);

const blankRow = (): Row => ({ date: today(), name: '', total: '', notes: '' });

const source = ref<string>('school');
const rows = ref<Row[]>([blankRow()]);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});

const showPaste = ref(false);
const pasteText = ref('');
const pasteError = ref<string | null>(null);

const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;

// Pipe-separated, one item per line: `YYYY-MM-DD | name | total? | notes?`.
// Lines that are empty or start with `#` are skipped (so the user can keep
// section comments in their list).
const parsePastedList = (raw: string): { rows: Row[]; rejected: number } => {
    const out: Row[] = [];
    let rejected = 0;

    for (const line of raw.split(/\r?\n/)) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) continue;

        const [datePart, namePart, totalPart, ...notesParts] = trimmed.split('|').map((s) => s.trim());

        if (!datePart || !ISO_DATE.test(datePart) || !namePart) {
            rejected++;
            continue;
        }

        out.push({
            date: datePart,
            name: namePart,
            total: totalPart ? totalPart.replace(/[^\d.]/g, '') : '',
            notes: notesParts.join(' | ').trim(),
        });
    }

    return { rows: out, rejected };
};

const applyPaste = () => {
    const { rows: parsed, rejected } = parsePastedList(pasteText.value);

    if (parsed.length === 0) {
        pasteError.value = `Couldn't parse any rows. Each line should look like: 2026-05-15 | Name | 250 | optional notes`;
        return;
    }

    rows.value = parsed;
    pasteError.value = rejected > 0
        ? `Loaded ${parsed.length} rows; skipped ${rejected} unparseable line(s).`
        : null;
    pasteText.value = '';
    showPaste.value = false;
};

const validRows = computed(() => rows.value.filter((r) => r.date && r.name.trim().length > 0));

const addRow = () => rows.value.push(blankRow());
const removeRow = (idx: number) => {
    rows.value.splice(idx, 1);
    if (rows.value.length === 0) addRow();
};

const reset = () => {
    rows.value = [blankRow()];
    source.value = 'school';
    errors.value = {};
};

const close = () => {
    if (submitting.value) return;
    emit('close');
};

const submit = () => {
    if (validRows.value.length === 0) {
        errors.value = { items: 'Add at least one row before saving.' };
        return;
    }

    submitting.value = true;
    errors.value = {};

    router.post(
        '/planner/bulk',
        {
            source: source.value || null,
            items: validRows.value.map((r) => ({
                date: r.date,
                name: r.name.trim(),
                notes: r.notes.trim() || null,
                total: r.total === '' ? null : Number(r.total),
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                emit('close');
            },
            onError: (e) => {
                errors.value = e as Record<string, string>;
            },
            onFinish: () => {
                submitting.value = false;
            },
        }
    );
};
</script>

<template>
    <DialogModal :show="show" max-width="3xl" @close="close">
        <template #title>
            <div class="flex items-center justify-between">
                <span>{{ $t('Plan a batch of scheduled items') }}</span>
                <button
                    type="button"
                    class="text-body-1/60 hover:text-body"
                    :disabled="submitting"
                    @click="close"
                >
                    <i class="fa fa-times" />
                </button>
            </div>
        </template>

        <template #content>
            <p class="text-sm text-body-1/70 mb-4">
                {{ $t("Paste your kid's school monthly activities or any list of dated to-dos. Each row becomes a Planner item that surfaces in Today on its date.") }}
            </p>

            <div class="mb-4 flex items-end gap-3 flex-wrap">
                <div class="w-64 max-w-full">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-body-1/60 mb-1">
                        {{ $t('Source (optional)') }}
                    </label>
                    <LogerInput v-model="source" placeholder="school, work, home..." />
                    <p class="text-xs text-body-1/50 mt-1">
                        {{ $t('Tag everything in this batch with the same origin so you can filter later.') }}
                    </p>
                </div>
                <button
                    type="button"
                    class="text-sm text-primary hover:underline whitespace-nowrap pb-6"
                    @click="showPaste = !showPaste"
                >
                    <i class="fa fa-paste mr-1" />
                    {{ showPaste ? $t('Hide paste') : $t('Paste a list') }}
                </button>
            </div>

            <div v-if="showPaste" class="mb-4 p-3 bg-base-lvl-2 rounded-md border border-base">
                <label class="block text-xs font-semibold uppercase tracking-wide text-body-1/60 mb-1">
                    {{ $t('Paste pipe-separated list') }}
                </label>
                <p class="text-xs text-body-1/60 mb-2">
                    {{ $t('Format:') }} <code class="text-primary">YYYY-MM-DD | name | total | notes</code>
                    {{ $t('— total and notes are optional. Empty lines and lines starting with # are skipped.') }}
                </p>
                <textarea
                    v-model="pasteText"
                    rows="6"
                    class="w-full px-2 py-1.5 text-sm font-mono bg-base-lvl-3 border border-base rounded-md text-body"
                    placeholder="2026-05-07 | Bring plant for Día del Árbol&#10;2026-05-15 | Canvas 8x12 for Mother's Day&#10;2026-05-27 | Pizza combo | 250 | Pro fondo banda MKR&#10;2026-05-29 | Planet costume"
                />
                <div class="mt-2 flex items-center justify-between">
                    <p v-if="pasteError" class="text-xs" :class="pasteError.startsWith('Loaded') ? 'text-body-1/70' : 'text-error'">
                        {{ pasteError }}
                    </p>
                    <span v-else class="text-xs text-body-1/40">{{ $t('Tip: ask Claude or GPT to convert your school PDF into this format.') }}</span>
                    <button
                        type="button"
                        class="text-sm text-primary font-semibold hover:underline ml-auto"
                        :disabled="!pasteText.trim()"
                        @click="applyPaste"
                    >
                        {{ $t('Parse → fill rows') }}
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <div class="grid grid-cols-12 gap-2 px-2 text-xs font-semibold uppercase tracking-wide text-body-1/60">
                    <div class="col-span-3">{{ $t('Date') }}</div>
                    <div class="col-span-4">{{ $t('Name') }}</div>
                    <div class="col-span-2">{{ $t('Amount') }}</div>
                    <div class="col-span-2">{{ $t('Notes') }}</div>
                    <div class="col-span-1"></div>
                </div>

                <div
                    v-for="(row, idx) in rows"
                    :key="idx"
                    class="grid grid-cols-12 gap-2 items-start"
                >
                    <input
                        v-model="row.date"
                        type="date"
                        class="col-span-3 px-2 py-1.5 text-sm bg-base-lvl-2 border border-base rounded-md text-body"
                    />
                    <input
                        v-model="row.name"
                        type="text"
                        :placeholder="$t('What needs to happen')"
                        class="col-span-4 px-2 py-1.5 text-sm bg-base-lvl-2 border border-base rounded-md text-body"
                    />
                    <input
                        v-model="row.total"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0"
                        class="col-span-2 px-2 py-1.5 text-sm bg-base-lvl-2 border border-base rounded-md text-body"
                    />
                    <input
                        v-model="row.notes"
                        type="text"
                        :placeholder="$t('Optional')"
                        class="col-span-2 px-2 py-1.5 text-sm bg-base-lvl-2 border border-base rounded-md text-body"
                    />
                    <button
                        type="button"
                        class="col-span-1 text-body-1/40 hover:text-error self-center"
                        :disabled="rows.length === 1 && submitting"
                        :title="$t('Remove row')"
                        @click="removeRow(idx)"
                    >
                        <i class="fa fa-trash" />
                    </button>
                </div>
            </div>

            <button
                type="button"
                class="mt-3 text-sm text-primary hover:underline"
                @click="addRow"
            >
                <i class="fa fa-plus mr-1" />{{ $t('Add another row') }}
            </button>

            <p v-if="errors.items" class="text-sm text-error mt-3">{{ errors.items }}</p>
            <p
                v-for="(msg, key) in errors"
                v-show="key.toString().startsWith('items.')"
                :key="key"
                class="text-xs text-error mt-1"
            >
                {{ msg }}
            </p>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <LogerButton variant="neutral" :disabled="submitting" @click="close">
                    {{ $t('Cancel') }}
                </LogerButton>
                <LogerButton
                    variant="inverse"
                    :disabled="submitting || validRows.length === 0"
                    @click="submit"
                >
                    {{ submitting ? $t('Saving...') : $t('Save {n} items', { n: validRows.length }) }}
                </LogerButton>
            </div>
        </template>
    </DialogModal>
</template>
