<template>
    <app-layout>
        <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">

            <aside class="w-4/12">
                <article class="rounded-md border bg-slate-600 py-2 px-5">
                    <section-title type="secondary"> Accounts</section-title>

                    <div class="mt-4 space-y-2">
                        <section>
                            <div v-for="account in accounts" :key="account.id" class="px-5 py-3 cursor-pointer rounded-md bg-slate-600 border-2">
                                <p>{{ account.name }}</p>
                                <strong class="mt-2 block">{{ account.balance }}</strong>
                                <button @click="deleteAccount(account.id)"> delete </button>
                            </div>
                        </section>

                    </div>
                    <AtButton @click="openAccountModal">
                        Add account
                    </AtButton>
                </article>
            </aside>
            <main class="w-8/12">
            </main>
        </div>
        <AccountModal v-if="isAccountModalOpen" :show="isAccountModalOpen" @close="isAccountModalOpen=false" />
    </app-layout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout'
    import SectionTitle from "@/Components/atoms/SectionTitle";
    import AccountModal from '../../../Components/organisms/AccountModal.vue';
    import { AtButton } from "atmosphere-ui";
    import { ref } from 'vue';
    import { useSelect } from '../../../utils/useSelects';
import { Inertia } from '@inertiajs/inertia';

    const props = defineProps({
        categories: {
            type: Array,
            default: () => []
        },
        accounts: {
            type: Array,
            default: () => []
        },
        accountDetailTypes: {
            type: Array,
            default: () => []
        },
    })

    const isAccountModalOpen = ref(false)
    const openAccountModal = () => {
        isAccountModalOpen.value = true
    }
    const deleteAccount = (id) => {
        Inertia.delete(`/accounts/${id}`, {
            onSuccess() {
                Inertia.reload()
            }
        })
    }

    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(props.categories, 'subcategories', 'categoryOptions');
</script>
