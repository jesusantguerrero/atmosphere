<script setup lang="ts">
    import { Link } from '@inertiajs/vue3';
    import { AtButton } from "atmosphere-ui"

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import CustomTable from "@/Components/atoms/CustomTable.vue";

    import cols from "./cols"
    import { router } from '@inertiajs/vue3';
    import { formatDate } from '@/utils';
import LogerButtonCircle from '@/Components/atoms/LogerButtonCircle.vue';

    defineProps({
        notifications: {
            type: Array,
            default() {
                return []
            }
        }
    });

    interface INotification {
        id: number;
    }

    const markAllAsRead = () => {
        router.patch(`/notifications`, {
            read_at: new Date()
        })
    }

    const markAsRead = (notification: INotification ) => {
        router.put(`/notifications/${notification.id}`, {
            read_at: new Date()
        })
    }
</script>


<template>
    <AppLayout title="Notifications">
        <main
        class="px-5 mx-auto mt-5 mb-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8"
      >
            <div class="w-full mt-6 rounded-md bg-base-lvl-3">
                <CustomTable
                    ref="AtTable"
                    :config="tableConfig"
                    :cols="cols"
                    :table-data="notifications"
                    :section="section"
                >
                <template #header-actions v-if="notifications.length">
                    <div class="flex items-center ml-auto space-x-2 justify-end mr-2">
                        <LogerButtonCircle @click="markAllAsRead" :keep-active-mode="false" class="hover:text-primary">
                            <span class="text-lg">
                                <IMdiEmailCheck  />
                            </span>
                        </LogerButtonCircle>

                    </div>
                </template>
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
                 <template #actions="{ scope : { row } }">
                    <div class="flex items-center ml-auto space-x-2">
                    <Link :href="row.data.link" class="ml-auto transition-colors rounded-md text-primary">
                        {{ row.data.cta }}
                    </Link>

                    <AtButton :href="row.data.link" class="ml-auto text-white transition-colors rounded-md bg-primary" @click="markAsRead(row)" v-if="!row.read_at">
                        Mark as read
                    </AtButton>
                    </div>
                </template>
                </CustomTable>
            </div>
        </main>
    </AppLayout>
</template>
