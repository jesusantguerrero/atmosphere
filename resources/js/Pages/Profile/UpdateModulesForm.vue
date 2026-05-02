<script lang="ts" setup>
    import { useForm, usePage } from '@inertiajs/vue3'
    import { computed } from 'vue'

    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import JetCheckbox from '@/Components/atoms/Checkbox.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'

    interface ModuleProp {
        id: number;
        name: string;
        alias: string;
        enabled: boolean;
    }

    const pageModules = computed<ModuleProp[]>(() => usePage().props.modules ?? [])

    const form = useForm({
        modules: pageModules.value.map((module) => ({
            name: module.name,
            enabled: Boolean(module.enabled),
        })),
    })

    const updateModules = () => {
        form.patch(route('user.modules.update'), {
            preserveScroll: true,
        })
    }
</script>


<template>
    <JetFormSection
        :title="$t('Available modules')"
        :description="$t('Enable only the modules you want to see in the navigation.')"
        @submitted="updateModules"
    >
        <template #form>
            <div class="col-span-6 space-y-3">
                <label
                    v-for="(module, index) in form.modules"
                    :key="module.name"
                    class="flex items-center justify-between px-4 py-3 border rounded-md cursor-pointer border-base-lvl-3 hover:bg-base-lvl-2"
                >
                    <span class="font-medium">{{ $t(pageModules[index]?.alias ?? module.name) }}</span>
                    <JetCheckbox v-model:checked="form.modules[index].enabled" />
                </label>
            </div>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                {{ $t('Saved') }}
            </JetActionMessage>

            <LogerButton :processing="form.processing">
                {{ $t('Save') }}
            </LogerButton>
        </template>
    </JetFormSection>
</template>
