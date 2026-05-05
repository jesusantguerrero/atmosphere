<script lang="ts" setup>
    import { inject, computed } from "vue";
    import { AtField } from "atmosphere-ui"
    import { NSelect } from "naive-ui";
    import { useForm, usePage  } from '@inertiajs/vue3'

    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";
    import LogerButton from "@/Components/atoms/LogerButton.vue";

    const props = defineProps<{
        user: Record<string, any>;
        crossTeamCategoryOptions?: Array<Record<string, any>>;
    }>();

    const pageProps = usePage().props;

    const form =  useForm({
        name: "Emergency fund",
        photo: null,
        watchlist_id: null,
        category_id: null
    });

    const injectedCategoryOptions = inject("categoryOptions", []);
    const categorySelectOptions = computed(() => {
        return props.crossTeamCategoryOptions?.length
            ? props.crossTeamCategoryOptions
            : injectedCategoryOptions;
    });

    const selectedTeamLabel = computed(() => {
        if (!form.category_id || !props.crossTeamCategoryOptions?.length) return null;
        const currentTeamId = pageProps.auth?.user?.current_team?.id;
        for (const group of props.crossTeamCategoryOptions) {
            const match = group.children?.find((c: any) => c.value === form.category_id);
            if (match) {
                const groupTeamId = Number(String(group.key).replace('team-', ''));
                return groupTeamId !== currentTeamId ? group.label : null;
            }
        }
        return null;
    });

    const updateProfileInformation = () => {
        form.post(route('budget-funds.store'), {
            errorBag: 'updateProfileInformation',
            preserveScroll: true,
        });
    }
</script>

<template>
    <JetFormSection
        :title="$t('Emergency fund')"
        :description="$t('Take a watchlist and a category to measure how many months of expenses cover your emergency allocation.')"
        @submitted="updateProfileInformation"
    >
        <template #form>
            <!-- Name -->
            <AtField class="col-span-6 sm:col-span-4"
                label="Emergency Fund Category"
                field="name"
                :errors="form.errors"
            >
                <NSelect
                    filterable
                    clearable
                    tag
                    size="large"
                    class="w-full"
                    v-model:value="form.category_id"
                    :default-expand-all="true"
                    :options="categorySelectOptions"
                />
                <p v-if="selectedTeamLabel" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t('Pulling balance from') }}: {{ selectedTeamLabel }}
                </p>
            </AtField>

            <!-- Email -->
            <AtField
                class="col-span-6 sm:col-span-4"
                label="Expenses watchlist"
                field="expenses"
                :errors="form.errors"
            >
                <LogerApiSimpleSelect
                    v-model="form.watchlist_id"
                    custom-label="name"
                    track-id="id"
                    placeholder="Add a payee"
                    endpoint="/api/finance/watchlist"
                    class="w-48 md:w-full"
                />
            </AtField>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <LogerButton :processing="form.processing" variant="secondary">
                Save
            </LogerButton>
        </template>
    </JetFormSection>
</template>
