<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="pb-4 bg-white sm:p-6 sm:pb-4">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <div class="grid grid-cols-3 overflow-hidden text-lg rounded-lg">
                    <div
                        v-for="type in transactionTypes"
                        :key="type.value"
                        :options="transactionTypes"
                        @click="form.direction = type.value"
                        class="py-1 font-bold text-center cursor-pointer hover:bg-gray-200"
                        :class="[form.direction == type.value ? 'bg-pink-500 hover:bg-pink-500 text-white' : 'text-gray-500 bg-gray-100']"
                    >
                        {{ type.label }}
                    </div>
                </div>

                <div class="mt-2">
                    <slot name="content">
                        <div>
                            <div class="flex space-x-2">
                                <at-field label="Date" class="w-3/12">
                                    <n-date-picker
                                        v-model:value="form.date"
                                        type="date"
                                    />
                                </at-field>

                                <at-field
                                    label="Description"
                                    class="w-5/12"
                                >
                                    <at-input type="text" required v-model="form.description" />
                                </at-field>

                                 <at-field label="Account" class="w-4/12">
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
                                <at-field
                                    label="Amount"
                                    class="w-4/12"
                                >
                                    <at-input type="number" v-model="form.amount" />
                                </at-field>
                            </div>
                        </div>

                        <div v-if="recurrence">
                            <div class="flex">
                                <at-field label="Repeat this transaction" class="w-full">
                                <n-select
                                    v-model:value="schedule_settings.frequency"
                                    :options="[
                                        {
                                            value: 'WEEKLY',
                                            label: 'Weekly'
                                        },
                                        {
                                            value: 'MONTHLY',
                                            label: 'Monthly'
                                        }
                                    ]"
                                />
                                </at-field>
                                <at-field label="frequencyLabel">
                                    <at-input
                                        type="number"
                                        v-model="schedule_settings.repeat_on_day_of_month"
                                    />
                                </at-field>
                            </div>
                            <div class="flex">
                                <at-field label="Ends" class="w-full">
                                <n-select
                                    v-model:value="schedule_settings.end_type"
                                    :options="[
                                        {
                                            value: 'NEVER',
                                            label: 'Never'
                                        },
                                        {
                                            value: 'DATE',
                                            label: 'At'
                                        },
                                        {
                                            value: 'COUNT',
                                            label: 'After'
                                        }
                                    ]"
                                />
                                </at-field>
                                <at-field label="Date" v-if="schedule_settings.end_type == 'DATE'">
                                    <n-date-picker
                                        v-model:value="schedule_settings.end_date"
                                        size="lg"
                                    />
                                </at-field>
                                <at-field label="Instances" v-if="schedule_settings.end_type == 'COUNT'">
                                    <at-input
                                        type="number"
                                        v-model="schedule_settings.count"
                                    />
                                </at-field>
                            </div>
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
    import { AtField, AtInput, AtButton } from "atmosphere-ui"
    import { reactive, toRefs } from '@vue/reactivity'
    import { inject } from '@vue/runtime-core'
    import { NSelect, NDatePicker } from "naive-ui";
    import { format } from 'date-fns'

    export default {
        emits: ['close'],

        components: {
            Modal,
            AtField,
            AtInput,
            AtButton,
            NSelect,
            NDatePicker
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
            },
            recurrence: {
                type: Boolean,
                default: false
            }
        },
        setup(props, { emit }) {
            const state = reactive({
                transactionTypes :[{
                    value: 'DEPOSIT',
                    label: 'Income'
                }, {
                    value: 'WITHDRAW',
                    label: 'Expense'
                }, {
                    value: 'ENTRY',
                    label: 'Transaction'
                }],
                frequencyLabel: 'every',
                schedule_settings: {
                    end_date: null,
                    count: null,
                    end_type: 'NEVER',
                    final_item_date: null,
                    interval: 1,
                    frequency: "monthly",
                    repeat_at_end_of_month: false,
                    repeat_on_day_of_month: null,
                    start_date: null,
                    timezone_id: "America/Santo_Domingo"
                },
                form: useForm({
                    name: '',
                    date: null,
                    description: '',
                    direction: 'WITHDRAW',
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
                const postRoute = props.recurrence ? route('budget.planned-transaction') : route('transactions.store');
                state.form
                .transform((form) => ({
                    ...form,
                    resource_type_id: 'MANUAL',
                    total: form.amount,
                    date:  format(new Date(form.date), 'yyyy-MM-dd'),
                    status: 'verified',
                    ...state.schedule_settings
                }))
                .post(postRoute, {
                    onSuccess: () => {
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
