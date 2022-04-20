<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg">
                    <slot name="title">Add a new account</slot>
                </h3>

                <div class="mt-2">
                    <slot name="content">
                        <div>
                            <AtField label="Category" class="w-full">
                                <NSelect
                                    filterable
                                    clearable
                                    tag
                                    v-model:value="form.category_id"
                                    :options="categoryOptions"
                                />
                            </AtField>
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

                            <div class="flex space-x-4">
                                <AtField
                                    label="Account ID"
                                    class="w-6/12"
                                >
                                    <AtInput v-model="accountDisplayId" readonly />
                                </AtField>

                                <AtField
                                    label="Account Label"
                                    class="w-6/12"
                                >
                                    <AtInput v-model="form.name" />
                                </AtField>
                            </div>

                            <at-field
                                label="Description"
                            >
                                <at-input v-model="form.description"></at-input>
                            </at-field>


                        </div>
                    </slot>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-gray-100">
            <at-button type="secondary" @click="close"> Cancel </at-button>
            <at-button class="text-white bg-pink-400" @click="submit"> Save </at-button>
        </div>
    </modal>
</template>

<script setup>
    import Modal from '@/Jetstream/Modal'
    import { useForm, usePage } from "@inertiajs/inertia-vue3"
    import { AtField, AtInput, AtButton } from "atmosphere-ui"
    import { reactive, toRefs } from '@vue/reactivity'
    import { computed, inject } from '@vue/runtime-core'
    import { NSelect } from "naive-ui";
    import Slug from "slug";
    import { makeOptions } from '../../utils/naiveui'

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
        }),
        accountDisplayId: computed(() => {
            return Slug(state.form.name, '_')
        })
    })
    const categoryOptions = inject('categoryOptions')
    const createCategory = async (createdLabel) => {
        if (typeof createdLabel == 'string') {
            category = await axios.post('/api/categories',
            { name: createdLabel, 'parent_id': 'expenses'} , {
                onSuccess: ({ data }) => {
                    return data;
                }
            })
        }
    }

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

    const { form, accountDisplayId } = toRefs(state)
</script>
