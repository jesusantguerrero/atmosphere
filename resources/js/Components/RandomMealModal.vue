<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-4 py-5 text-center text-white bg-primary sm:p-6 sm:pb-4">
            <div class="justify-center sm:flex sm:items-start">
                <div class="mt-3 text-center sm:mt-0 sm:ml-4">
                    <h3 class="w-full text-4xl font-bold text-white">
                        <slot name="title">
                            The meal is:
                        </slot>
                    </h3>

                    <div class="mt-5 mb-5">
                        <slot name="content">
                            <div v-if="meal" class="mt-5 text-2xl">
                                {{ meal.name }}
                            </div>
                            <div v-else class="mt-5 text-2xl">
                                loading ...
                            </div>
                        </slot>
                        <div class="mt-5">
                            <at-button
                                class="text-gray-700 bg-base-lvl-1"
                                :disabled="!meal"
                                @click="submit()">
                                    Random
                                </at-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </modal>
</template>

<script>
    import Modal from '@/Jetstream/Modal.vue'
    import { AtField, AtInput, AtButton } from "atmosphere-ui"
    import { reactive, toRefs } from '@vue/reactivity'
    import Meal from './MealSection.vue'
    import { watch } from '@vue/runtime-core'
    import axios from 'axios'

    export default {
        emits: ['close'],

        components: {
            Modal,
            AtField,
            AtInput,
            AtButton,
            Meal
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
            meals: {
                type: Array,
                default() {
                    return []
                }
            },
            date: {
                default: new Date()
            },
            title: {
                type: String
            }
        },

        setup(props, { emit }) {
            const state = reactive({
                meal: null
            })

            const { show } = toRefs(props)

            const close = () =>  {
                emit('close')
            }

            watch(show, () => {
                if (show.value) {
                    submit()
                }
            }, { immediate: true })

            const submit = () => {
                state.meal = null;
                axios.get('/meals-random').then(({ data }) => {
                    state.meal = data;
                })
            }

            return {
                ...toRefs(state),
                submit,
                close
            }
        }
    }
</script>
