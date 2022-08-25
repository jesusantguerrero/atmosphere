<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-10 border-4 border-white bg-gray-50 rounded-md py-2">
                <div class="px-5 text-gray-600 font-bold">
                    Notifications
                </div>
            </div>

            <div class="w-full bg-base-lvl-3 rounded-md px-4">
                <CustomTable
                    ref="AtTable"
                    :config="tableConfig"
                    :cols="cols"
                    :table-data="notifications"
                    :section="section"
                >
                 <template #data="{ scope }">
                        <div class="py-3 pl-4 flex space-between w-full">
                            {{ scope.row.data.message }}
                        </div>
                </template>
                 <template #actions="{ scope }">
                    <div class="flex items-center ml-auto space-x-2">
                    <Link :href="scope.row.data.link" class="text-primary ml-auto rounded-md transition-colors" @click="markAsRead(scope.row)">
                        {{ scope.row.data.cta }}
                    </Link>

                    <AtButton :href="scope.row.data.link" class="bg-primary text-white ml-auto rounded-md transition-colors" @click="markAsRead(scope.row)" v-if="!scope.row.read_at">
                        Mark as read
                    </AtButton>
                    </div>
                </template>
                </CustomTable>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import { Link } from '@inertiajs/inertia-vue3';
    import { reactive, toRefs } from 'vue'
    import { AtButton } from "atmosphere-ui"

    import AppLayout from '@/Layouts/AppLayout.vue'
    import CustomTable from "@/Components/atoms/CustomTable.vue";

    import cols from "./cols"
    import { Inertia } from '@inertiajs/inertia';

    const props = defineProps({
            notifications: {
                type: Array,
                default() {
                    return []
                }
            }
    });

    const state = reactive({
        tableSearchOptions: {
            resourceUrl: "/projects?sort=surename"
        },
        tableConfig: {
            resourceUrl: "/projects",
            selectable: true,
            pagination: true,
            searchBar: ["search", "filter", "dates", "add", "actions"],
            dataTemplate: {
                name: "week-pager",
                filter: "date"
            }
        }
    });

    const markAsRead = (notification) => {
        Inertia.patch(`/notifications/${notification.id}`, {
            read_at: new Date()
        })
    }
</script>
