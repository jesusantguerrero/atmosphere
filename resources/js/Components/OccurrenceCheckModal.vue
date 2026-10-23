<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import OccurrenceRuleFields from './OccurrenceRuleFields.vue';
import Modal from '@/Components/atoms/Modal.vue';
import OccurrencePreview from './OccurrencePreview.vue';
import { occurrenceFormData, occurrenceRuleFields, hasOccurrenceRule } from '@/domains/housing/occurrenceForm';

const props = defineProps({
    show: { default: false },
    maxWidth: { default: '3xl' },
    closeable: { default: true },
    formData: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:show', 'saved']);
const form = useForm(occurrenceFormData());
const activeTab = ref('information');
const tabs = [
    { value: 'information', label: 'Information' },
    { value: 'rule', label: 'Detection rule' },
    { value: 'preview', label: 'Preview' },
];
const hasRule = computed(() => hasOccurrenceRule(form.conditions));
const isEditing = computed(() => Boolean(form.id));
const close = () => { if (!form.processing) emit('update:show', false); };

watch(() => [props.show, props.formData], () => {
    if (!props.show) return;
    Object.assign(form, occurrenceFormData(props.formData));
    form.clearErrors();
    activeTab.value = 'information';
    nextTick(() => document.getElementById('occurrence-name')?.focus());
}, { immediate: true });

const keepFocus = (event: KeyboardEvent) => {
    if (event.key !== 'Tab') return;
    const fields = [...(event.currentTarget as HTMLElement).querySelectorAll<HTMLElement>('button, input, select, [tabindex="0"]')]
        .filter(field => field.tabIndex >= 0 && !field.hasAttribute('disabled') && field.offsetParent !== null);
    const destination = event.shiftKey && document.activeElement === fields[0]
        ? fields[fields.length - 1]
        : !event.shiftKey && document.activeElement === fields[fields.length - 1] ? fields[0] : null;
    if (destination) { event.preventDefault(); destination.focus(); }
};

const focusTab = (index: number) => {
    const tab = tabs[(index + tabs.length) % tabs.length];
    activeTab.value = tab.value;
    document.getElementById(`occurrence-tab-${tab.value}`)?.focus();
};
const addGroup = () => form.conditions.groups.push({ match: 'any', ...occurrenceRuleFields() });
const submit = () => {
    if (form.processing || !form.name.trim()) return;
    form.name = form.name.trim();
    const options = {
        preserveScroll: true,
        onSuccess: () => { emit('saved', form.data()); emit('update:show', false); },
        onError: () => { activeTab.value = form.errors.name ? 'information' : 'rule'; },
    };
    if (form.id) form.put(route('occurrence.update', form.id), options);
    else form.post(route('occurrence.store'), options);
};
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" :closeable="closeable && !form.processing" @close="close">
        <form class="flex max-h-[90dvh] w-full flex-col text-body" role="dialog" aria-modal="true" aria-labelledby="occurrence-modal-title" @submit.prevent="submit" @keydown="keepFocus">
            <header class="flex items-start justify-between gap-4 border-b border-base px-5 py-5 sm:px-6">
                <div class="min-w-0">
                    <h2 id="occurrence-modal-title" class="text-xl font-semibold">{{ $t(isEditing ? 'Edit occurrence' : 'New occurrence') }}</h2>
                    <p class="mt-1 text-sm text-body-1/70">{{ $t('Track when something last happened and how often it repeats.') }}</p>
                </div>
                <button type="button" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-xl hover:bg-base-lvl-2 focus-visible:ring-2 focus-visible:ring-primary" :aria-label="$t('Close')" :disabled="form.processing" @click="close">×</button>
            </header>
            <nav class="grid shrink-0 grid-cols-3 gap-1 border-b border-base px-3 pt-2 sm:px-5" role="tablist" :aria-label="$t('Occurrence settings')">
                <button v-for="(tab, index) in tabs" :id="`occurrence-tab-${tab.value}`" :key="tab.value" type="button" role="tab" :aria-selected="activeTab === tab.value" :aria-controls="`occurrence-panel-${tab.value}`" :tabindex="activeTab === tab.value ? 0 : -1" class="min-h-[48px] border-b-2 px-2 py-3 text-sm font-medium focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary" :class="activeTab === tab.value ? 'border-primary text-primary' : 'border-transparent text-body-1/70 hover:text-body'" @click="activeTab = tab.value" @keydown.right.prevent="focusTab(index + 1)" @keydown.left.prevent="focusTab(index - 1)" @keydown.home.prevent="focusTab(0)" @keydown.end.prevent="focusTab(tabs.length - 1)">{{ $t(tab.label) }}</button>
            </nav>
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <section v-if="activeTab === 'information'" id="occurrence-panel-information" role="tabpanel" aria-labelledby="occurrence-tab-information" class="space-y-6">
                    <div>
                        <label for="occurrence-name" class="mb-2 block text-sm font-semibold">{{ $t('Name') }}</label>
                        <input id="occurrence-name" v-model="form.name" autofocus required maxlength="255" :aria-invalid="Boolean(form.errors.name)" :aria-describedby="form.errors.name ? 'occurrence-name-error' : undefined" class="min-h-[44px] w-full rounded-lg border border-base bg-base-lvl-2 px-3 text-body focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" :placeholder="$t('For example: Groceries or oil change')" />
                        <p v-if="form.errors.name" id="occurrence-name-error" class="mt-2 text-sm text-danger">{{ form.errors.name }}</p>
                    </div>
                    <label class="flex cursor-pointer items-start justify-between gap-4 border-b border-base pb-5">
                        <span><span class="block font-medium">{{ $t('Tracking enabled') }}</span><span class="mt-1 block text-sm text-body-1/70">{{ $t('Pause this occurrence without deleting its history.') }}</span></span>
                        <input v-model="form.is_active" type="checkbox" class="mt-1 h-6 w-6 shrink-0 accent-primary" />
                    </label>
                    <fieldset class="space-y-4" :disabled="!form.is_active">
                        <legend class="mb-3 font-semibold">{{ $t('Reminders') }}</legend>
                        <label class="flex min-h-[44px] cursor-pointer items-start gap-3" :class="{ 'opacity-50': !form.is_active }"><input v-model="form.notify_on_avg" type="checkbox" class="mt-1 h-5 w-5 shrink-0 accent-primary" /><span><span class="block text-sm font-medium">{{ $t('Based on your usual frequency') }}</span><span class="mt-1 block text-sm text-body-1/70">{{ $t('Use the average time between your previous occurrences.') }}</span></span></label>
                        <label class="flex min-h-[44px] cursor-pointer items-start gap-3" :class="{ 'opacity-50': !form.is_active }"><input v-model="form.notify_on_last_count" type="checkbox" class="mt-1 h-5 w-5 shrink-0 accent-primary" /><span><span class="block text-sm font-medium">{{ $t('Based on the previous interval') }}</span><span class="mt-1 block text-sm text-body-1/70">{{ $t('Use the time between your two most recent occurrences.') }}</span></span></label>
                    </fieldset>
                </section>
                <section v-if="activeTab === 'rule'" id="occurrence-panel-rule" role="tabpanel" aria-labelledby="occurrence-tab-rule" class="space-y-6">
                    <p class="text-sm text-body-1/70">{{ $t('Detect occurrences from verified transactions using the fields below.') }}</p>
                    <div class="rounded-xl border border-base p-4 space-y-4">
                        <label class="flex flex-wrap items-center justify-between gap-3 text-sm font-semibold">{{ $t('Match the following conditions and groups') }}
                            <select v-model="form.conditions.match" class="min-h-[44px] rounded-lg border border-base bg-base-lvl-2 px-3"><option value="all">{{ $t('All conditions (AND)') }}</option><option value="any">{{ $t('Any condition (OR)') }}</option></select>
                        </label>
                        <OccurrenceRuleFields :fields="form.conditions" prefix="occurrence-root" />
                    </div>
                    <div v-for="(group, index) in form.conditions.groups" :key="index" class="space-y-4 rounded-xl border border-primary/30 bg-primary/5 p-4">
                        <div class="flex items-center justify-between gap-3"><h3 class="font-semibold">{{ $t('Condition group') }} {{ index + 1 }}</h3><button type="button" class="min-h-[44px] px-2 text-sm text-danger" @click="form.conditions.groups.splice(index, 1)">{{ $t('Remove group') }}</button></div>
                        <label class="flex flex-wrap items-center justify-between gap-3 text-sm">{{ $t('Inside this group') }}<select v-model="group.match" class="min-h-[44px] rounded-lg border border-base bg-base-lvl-2 px-3"><option value="all">{{ $t('All conditions (AND)') }}</option><option value="any">{{ $t('Any condition (OR)') }}</option></select></label>
                        <OccurrenceRuleFields :fields="group" :prefix="`occurrence-group-${index}`" />
                    </div>
                    <button type="button" :disabled="form.conditions.groups.length >= 5" class="min-h-[44px] rounded-lg border border-base px-4 text-sm font-medium text-primary disabled:opacity-40" @click="addGroup">+ {{ $t('Add condition group') }}</button>
                    <details class="rounded-xl border border-base p-4">
                        <summary class="cursor-pointer py-2 font-semibold">{{ $t('Always exclude') }}</summary>
                        <p class="my-3 text-sm text-body-1/70">{{ $t('Matching any exclusion skips the transaction, even when the rule above matches.') }}</p>
                        <OccurrenceRuleFields :fields="form.conditions.exclude" prefix="occurrence-exclude" />
                    </details>
                    <p v-if="!hasRule" class="rounded-lg bg-base-lvl-2 px-4 py-3 text-sm text-body-1/70">{{ $t('No automatic rule yet. You can still register occurrences manually.') }}</p>
                    <p v-for="(message, field) in form.errors" :key="field" class="text-sm text-danger" role="alert">{{ message }}</p>
                </section>
                <section v-if="activeTab === 'preview'" id="occurrence-panel-preview" role="tabpanel" aria-labelledby="occurrence-tab-preview">
                    <OccurrencePreview :conditions="form.conditions" />
                </section>
            </div>
            <footer class="flex shrink-0 items-center justify-end gap-3 border-t border-base bg-base-lvl-2 px-5 py-4 sm:px-6">
                <button type="button" class="min-h-[44px] rounded-lg border border-base px-4 text-sm font-medium focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50" :disabled="form.processing" @click="close">{{ $t('Cancel') }}</button>
                <button type="submit" class="min-h-[44px] rounded-lg bg-primary px-5 text-sm font-semibold text-white focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50" :disabled="!form.name.trim() || form.processing">{{ $t(form.processing ? 'Saving…' : isEditing ? 'Save changes' : 'Create occurrence') }}</button>
            </footer>
        </form>
    </Modal>
</template>

