<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-4 pt-5 pb-4 bg-base-lvl-1 sm:p-6 sm:pb-4">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg">
                    <slot name="title">Add a new category</slot>
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
                                    @update:value="createCategory"
                                    :default-expand-all="true"
                                    :options="categoryOptions"
                                />
                            </AtField>

                            <AtField
                                label="Category Name"
                            >
                                <AtInput v-model="form.name" />
                            </AtField>

                            <AtField
                                label="Description"
                            >
                                <AtInput v-model="form.description" />
                            </AtField>


                        </div>
                    </slot>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-gray-100">
            <at-button type="secondary" @click="close"> Cancel </at-button>
            <at-button class="text-white bg-primary" @click="submit"> Save </at-button>
        </div>
    </modal>
</template>

<script>
    import Modal from '@/Components/atoms/Modal.vue'
    import { useForm } from "@inertiajs/vue3"
    import { AtField, AtInput, AtButton } from "atmosphere-ui"
    import { reactive, toRefs } from '@vue/reactivity'
    import { computed, inject } from '@vue/runtime-core'
    import { NSelect } from "naive-ui";
    import Slug from "slug";

    export default {
        emits: ['close'],

        components: {
            Modal,
            AtField,
            AtInput,
            AtButton,
            NSelect,
        },

        props: {
            show: {
                default: false
            },
            maxWidth: {
                default: '2xl'
            },
            closeable: {
                default: true
            },
        },
        setup(props, { emit }) {
            const state = reactive({
                form: useForm({
                    name: '',
                    category_id: null,
                    display_id: '',
                }),
                categoryOptions: computed(() => {
                    const options = [...inject('categoryOptions', [])]
                    return options.map(( category) => {
                        const clone = {...category};
                        clone.type = "",
                        clone.value = clone.key;
                        delete clone.children;
                        return clone
                    })
                })
            })

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

            return {
                ...toRefs(state),
                createCategory,
                submit,
                close
            }
        }
    }
</script>
