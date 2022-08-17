<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-4 pt-5 pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg">
                    <slot name="title">Add Account</slot>
                </h3>

                <div class="mt-2">
                    <slot name="content">
                        <div>
                            <AtField label="Detail Type" class="w-full">
                                <NSelect
                                    filterable
                                    clearable
                                    tag
                                    v-model:value="form.account_detail_type_id"
                                    :default-expand-all="true"
                                    :options="detailOptions"
                                />
                            </AtField>

                            <AtField
                                label="Account Label"
                            >
                                <LogerInput v-model="form.name" />
                            </AtField>

                            <AtField
                                label="Opening Balance"
                            >
                                <LogerInput v-model="form.opening_balance" type="number" />
                            </AtField>
                        </div>
                    </slot>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-base-lvl-2">
            <AtButton type="secondary" @click="close" rounded> Cancel </AtButton>
            <AtButton class="text-white bg-primary" @click="submit" rounded> Save </AtButton>
        </div>
    </modal>
</template>

<script setup>
    import Modal from '@/Jetstream/Modal.vue'
    import { useForm, usePage } from "@inertiajs/inertia-vue3"
    import { AtField, AtButton } from "atmosphere-ui"
    import { reactive, toRefs } from '@vue/reactivity'
    import { computed } from '@vue/runtime-core'
    import { NSelect } from "naive-ui";
    import Slug from "slug";
    import { makeOptions } from '@/utils/naiveui'
    import LogerInput from '@/Components/atoms/LogerInput.vue'

    const emit = defineEmits(['close'])
    defineProps({
            show: {
                default: false
            },
            maxWidth: {
                default: '2xl'
            },
            closeable: {
                default: true
            },
    });

    const state = reactive({
        form: useForm({
            name: '',
            category_id: null,
            account_detail_type_id: null,
            display_id: '',
            description: '',
            opening_balance: 0
        }),
        accountDisplayId: computed(() => {
            return Slug(state.form.name, '_')
        })
    })

    const close = () =>  {
        emit('close')
    }

    const submit = () => {

        state.form
        .transform((form) => {
            form.display_id = Slug(form.name, '_')
            return form;
        })
        .post(route('accounts.store'), {
            onSuccess: ({ data }) => {
                emit('close')
                state.form.reset();
            },
        })
    }

    const detailTypes = usePage().props.value.accountDetailTypes
    const detailOptions = makeOptions(detailTypes, ['id', 'label'])

    const { form } = toRefs(state)
</script>
