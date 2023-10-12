<script setup lang="ts">
    import { Link } from '@inertiajs/vue3';
    import { reactive } from 'vue'
    import { AtButton } from "atmosphere-ui"

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import CustomTable from "@/Components/atoms/CustomTable.vue";

    import cols from "./cols"
    import { router } from '@inertiajs/vue3';
    import { formatDate } from '@/utils';

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
        router.patch(`/notifications/${notification.id}`, {
            read_at: new Date()
        })
    }
</script>


<template>
    <AppLayout title="Notifications">
        <div class="px-2 py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="w-full rounded-md bg-base-lvl-3">
                <CustomTable
                    ref="AtTable"
                    :config="tableConfig"
                    :cols="cols"
                    :table-data="notifications"
                    :section="section"
                >
                 <template #data="{ scope }">

                    <article class="w-full py-2 pl-4 space-between">
                        <header>
                            {{ scope.row.data.message }}
                        </header>
                        <footer class="mt-4 font-bold border-t border-base-lvl-2 text-secondary">
                            {{ formatDate(scope.row.created_at.slice(0, 10)) }}
                        </footer>
                    </article>

                </template>
                 <template #actions="{ scope }">
                    <div class="flex items-center ml-auto space-x-2">
                    <Link :href="scope.row.data.link" class="ml-auto transition-colors rounded-md text-primary" @click="markAsRead(scope.row)">
                        {{ scope.row.data.cta }}
                    </Link>

                    <AtButton :href="scope.row.data.link" class="ml-auto text-white transition-colors rounded-md bg-primary" @click="markAsRead(scope.row)" v-if="!scope.row.read_at">
                        Mark as read
                    </AtButton>
                    </div>
                </template>
                </CustomTable>
            </div>
        </div>
    </AppLayout>
</template>
