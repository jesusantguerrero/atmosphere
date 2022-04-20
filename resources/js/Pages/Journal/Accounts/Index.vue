<template>
    <app-layout>
        <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">

            <aside class="w-4/12">
                <article class="rounded-md border bg-white py-2 px-5">
                    <section-title type="secondary"> Accounts</section-title>

                    <div class="mt-4 space-y-2">
                        <div v-for="category in categories" class="cursor-pointer rounded-md bg-white border-2">
                            <header class="flex justify-between px-5 py-3 w-full" @click="category.expanded = !category.expanded ">
                                <span>
                                    {{ category.name }}
                                </span>
                                    <i class="fa fa-chevron-down"></i>
                            </header>
                            <template v-if="category.expanded">
                                <article v-for="subcategory in category.subcategories" class="px-5 py-1">
                                    <section>
                                        {{ subcategory.name }}
                                    </section>
                                    <section>
                                        <div v-for="account in subcategory.accounts" :key="account.id" class="px-5 py-3 cursor-pointer rounded-md bg-white border-2">
                                            <p>{{ account.name }}</p>
                                            <strong class="mt-2 block">{{ account.balance }}</strong>
                                        </div>
                                    </section>
                                </article>
                            </template>
                        </div>
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

    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(props.categories, 'subcategories', 'categoryOptions');
</script>
