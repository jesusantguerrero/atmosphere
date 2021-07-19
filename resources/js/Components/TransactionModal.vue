<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3 class="text-lg">
                    <slot name="title">Register transaction</slot>
                </h3>

                <div class="mt-2">
                    <slot name="content">
                        <div>
                            <div class="flex space-x-3">
                                <at-field label="Category" class="w-full">
                                    <n-select
                                        filterable
                                        clearable
                                        tag
                                        v-model:value="form.category_id"
                                        @update:value="createCategory"
                                        :default-expand-all="true"
                                        :options="categoryOptions"
                                    />
                                </at-field>

                                <at-field label="Account" class="w-full">
                                    <n-select
                                        filterable
                                        clearable
                                        tag
                                        v-model:value="form.account_id"
                                        @update:value="createCategory"
                                        :default-expand-all="true"
                                        :options="accountsOptions"
                                    />
                                </at-field>
                            </div>

                            <at-field
                                label="Description"
                            >
                                <at-input type="text" required v-model="form.description" />
                            </at-field>

                            <at-field
                                label="Amount"
                            >
                                <at-input type="number" v-model="form.amount" />
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

<script>
    import Modal from '@/Jetstream/Modal'
    import { useForm } from "@inertiajs/inertia-vue3"
    import { AtField, AtInput, AtButton } from "atmosphere-ui/dist/atmosphere-ui.es.js"
    import { reactive, toRefs } from '@vue/reactivity'
    import { inject } from '@vue/runtime-core'
    import { NSelect } from "naive-ui";
    import { format } from 'date-fns'

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
            categories: {
                type: Array,
                default: []
            },
            accounts: {
                type: Array,
                default: []
            }
        },
        setup(props, { emit }) {
            const state = reactive({
                form: useForm({
                    name: '',
                    description: '',
                    isIncome: false,
                    category_id: null,
                    account_id: null,
                    display_id: '',
                    amount: 0,
                }),
            })

            const categoryOptions = inject('categoryOptions', [])
            const accountsOptions = inject('accountsOptions', [])

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
                    form.direction = form.isIncome ? 'DEPOSIT' : 'WITHDRAW';
                    form.resource_type_id = 'MANUAL';
                    form.total = form.amount
                    form.date = format(new Date(), 'yyyy-MM-dd');
                    return form;
                })
                .post(route('transactions.store'), {
                    onSuccess: ({ data }) => {
                        emit('close')
                        state.form.reset();
                    },
                })
            }

            return {
                ...toRefs(state),
                createCategory,
                accountsOptions,
                categoryOptions,
                submit,
                close
            }
        }
    }
</script>
