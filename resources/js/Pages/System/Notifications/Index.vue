<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { format, formatDistanceToNow, parseISO } from 'date-fns';

import AppLayout from '@/Components/templates/AppLayout.vue';
import CustomTable from '@/Components/atoms/CustomTable.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

import cols from './cols';

interface NotificationItem {
    id: string;
    type: string;
    read_at: string | null;
    created_at: string;
    data: {
        message?: string;
        cta?: string;
        link?: string;
        planner_id?: number;
        planner_name?: string;
    };
}

interface PaginatedNotifications {
    data: NotificationItem[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    notifications: PaginatedNotifications;
    filter: 'unread' | 'all';
    unreadCount: number;
}>();

// Map notification class -> icon + color + label so users can triage at a glance.
// Falls back to a generic info treatment for any type not listed.
const TYPE_META: Record<string, { icon: string; color: string; label: string }> = {
    'App\\Notifications\\WatchlistThresholdAlert': { icon: 'fa-bell', color: 'text-error border-error', label: 'Watchlist alert' },
    'App\\Notifications\\WatchlistAutoSuggestionAlert': { icon: 'fa-lightbulb', color: 'text-secondary border-secondary', label: 'Suggestion' },
    'App\\Notifications\\BillingCycleCutAlert': { icon: 'fa-credit-card', color: 'text-warning border-warning', label: 'Billing cycle' },
    'App\\Notifications\\OccurrenceAlert': { icon: 'fa-clock', color: 'text-primary border-primary', label: 'Reminder' },
    'App\\Notifications\\PlannedAlert': { icon: 'fa-calendar', color: 'text-primary border-primary', label: 'Planned' },
    'App\\Notifications\\TransactionsImported': { icon: 'fa-file-import', color: 'text-success border-success', label: 'Imported' },
    'App\\Notifications\\EntryGenerated': { icon: 'fa-file', color: 'text-body-1 border-base', label: 'Entry' },
};

const meta = (type: string) => TYPE_META[type] ?? { icon: 'fa-info-circle', color: 'text-body-1 border-base-lvl-1', label: 'Info' };

const list = computed(() => props.notifications?.data ?? []);

const titleLabel = computed(() => {
    return props.unreadCount > 0 ? `Notifications (${props.unreadCount})` : 'Notifications';
});

const safeRelative = (iso: string) => {
    try {
        return formatDistanceToNow(parseISO(iso), { addSuffix: true });
    } catch (e) {
        return iso;
    }
};

const safeFullDate = (iso: string) => {
    try {
        return format(parseISO(iso), 'PPp');
    } catch (e) {
        return iso;
    }
};

const setFilter = (filter: 'unread' | 'all') => {
    if (filter === props.filter) return;
    router.get('/notifications', { filter }, { preserveScroll: false, replace: true });
};

const goToPage = (page: number) => {
    router.get('/notifications', { filter: props.filter, page }, { preserveScroll: false });
};

const markAllAsRead = () => {
    router.patch('/notifications', { read_at: new Date() }, { preserveScroll: true });
};

const markAsRead = (notification: NotificationItem) => {
    if (notification.read_at) return;
    router.put(`/notifications/${notification.id}`, { read_at: new Date() }, { preserveScroll: true });
};

// Click CTA → mark as read in the background, then navigate.
// Two-button friction (open then mark read) is the #1 reported UX issue here.
const visitCta = (notification: NotificationItem) => {
    if (!notification.data?.link) return;
    markAsRead(notification);
    router.visit(notification.data.link);
};

// Inline planner actions for PlannedAlert notifications. Both endpoints
// flip the schedule's completed_at, which is what the reminder cron now
// (post-bug-fix) checks before re-firing. Once flipped, no more daily
// notifications for this bill.
const isPlannedAlert = (notification: NotificationItem) =>
    notification.type === 'App\\Notifications\\PlannedAlert' && !!notification.data.planner_id;

const markPlannerAsPaid = (notification: NotificationItem) => {
    const id = notification.data.planner_id;
    if (!id) return;
    router.patch(`/planner/${id}/complete`, {}, {
        preserveScroll: true,
        onSuccess: () => markAsRead(notification),
    });
};

const cancelPlannerReminders = (notification: NotificationItem) => {
    const id = notification.data.planner_id;
    if (!id) return;
    if (!confirm('Stop reminders for this planned item? You can still find it in the planner.')) return;
    router.patch(`/planner/${id}/cancel`, {}, {
        preserveScroll: true,
        onSuccess: () => markAsRead(notification),
    });
};

const rowClass = (row: NotificationItem) => {
    const m = meta(row.type);
    if (row.read_at) {
        return 'border-l-4 border-transparent opacity-60';
    }
    // Unread rows get a colored stripe matching their type.
    return `border-l-4 ${m.color.split(' ').find((c) => c.startsWith('border-')) || ''}`;
};
</script>

<template>
    <AppLayout :title="titleLabel">
        <main class="px-5 mx-auto mt-5 mb-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="w-full mt-6 rounded-md bg-base-lvl-3">
                <header class="flex items-center justify-between px-5 py-3 border-b border-base">
                    <div class="flex items-center gap-1 bg-base-lvl-2 rounded-md p-1">
                        <button
                            type="button"
                            class="px-3 py-1 text-sm rounded transition"
                            :class="filter === 'unread' ? 'bg-primary text-white shadow' : 'text-body-1 hover:text-body'"
                            @click="setFilter('unread')"
                        >
                            {{ $t('Unread') }}
                            <span v-if="unreadCount > 0" class="ml-1 text-xs opacity-80">{{ unreadCount }}</span>
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1 text-sm rounded transition"
                            :class="filter === 'all' ? 'bg-primary text-white shadow' : 'text-body-1 hover:text-body'"
                            @click="setFilter('all')"
                        >
                            {{ $t('All') }}
                        </button>
                    </div>
                    <LogerButton
                        v-if="unreadCount > 0"
                        variant="inverse"
                        @click="markAllAsRead"
                    >
                        {{ $t('Mark all as read') }}
                    </LogerButton>
                </header>

                <CustomTable
                    v-if="list.length"
                    :cols="cols"
                    :table-data="list"
                    :row-class="rowClass"
                    hide-empty-text
                >
                    <template #data="{ scope: { row } }">
                        <article class="flex items-start gap-3 px-2 py-3">
                            <div class="mt-0.5 text-lg">
                                <i class="fa" :class="[meta(row.type).icon, meta(row.type).color.split(' ').find((c) => c.startsWith('text-'))]" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-body break-words" :class="row.read_at ? 'font-normal' : 'font-semibold'">
                                    {{ row.data.message || $t('Notification') }}
                                </p>
                                <p class="mt-1 text-xs text-body-1/70">
                                    <span class="uppercase tracking-wide font-medium">{{ meta(row.type).label }}</span>
                                    <span class="mx-2">·</span>
                                    <span :title="safeFullDate(row.created_at)">{{ safeRelative(row.created_at) }}</span>
                                </p>
                            </div>
                        </article>
                    </template>

                    <template #actions="{ scope: { row } }">
                        <div class="flex items-center justify-end gap-3 px-2 py-3">
                            <!-- Inline planner actions: only render for PlannedAlert
                                 notifications that have a planner_id. Older notifications
                                 (created before the bug fix that added planner_id to the
                                 payload) won't show these and will rely on the Open CTA. -->
                            <template v-if="isPlannedAlert(row)">
                                <button
                                    type="button"
                                    class="text-sm text-success hover:underline font-medium"
                                    :title="$t('Mark this bill as paid — stops daily reminders')"
                                    @click="markPlannerAsPaid(row)"
                                >
                                    <i class="fa fa-check-circle mr-1" />
                                    {{ $t('Mark as paid') }}
                                </button>
                                <button
                                    type="button"
                                    class="text-xs text-body-1/60 hover:text-body-1"
                                    :title="$t('Stop reminders for this item')"
                                    @click="cancelPlannerReminders(row)"
                                >
                                    {{ $t('Stop reminders') }}
                                </button>
                            </template>

                            <button
                                v-if="row.data.link"
                                type="button"
                                class="text-sm text-primary hover:underline"
                                @click="visitCta(row)"
                            >
                                {{ row.data.cta || $t('Open') }}
                            </button>
                            <button
                                v-if="!row.read_at"
                                type="button"
                                class="text-xs text-body-1 hover:text-body"
                                :title="$t('Mark as read')"
                                :aria-label="$t('Mark as read')"
                                @click="markAsRead(row)"
                            >
                                <i class="fa fa-check" />
                            </button>
                        </div>
                    </template>
                </CustomTable>

                <!-- Empty state lives OUTSIDE CustomTable so it gets a proper full-width
                     centered layout. Inside CustomTable, the empty slot renders inside a
                     <td colspan> with display:flex applied, which collides with the fixed
                     col widths and offsets the content visually. -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center w-full py-16 px-6 text-center"
                >
                    <div class="text-5xl mb-3">{{ filter === 'unread' ? '🎉' : '📭' }}</div>
                    <h3 class="text-lg font-bold text-body">
                        {{ filter === 'unread' ? $t('All caught up') : $t('No notifications yet') }}
                    </h3>
                    <p class="mt-1 text-sm text-body-1 max-w-md">
                        {{ filter === 'unread'
                            ? $t('Watchlist alerts, billing cycle reminders, and other updates show up here.')
                            : $t('Notifications appear here when watchlist thresholds, billing cycles, or reminders trigger.')
                        }}
                    </p>
                    <Link
                        href="/dashboard"
                        class="mt-4 px-4 py-2 text-sm rounded-md text-primary border border-primary hover:bg-primary hover:text-white transition"
                    >
                        {{ $t('Back to Dashboard') }}
                    </Link>
                </div>

                <footer
                    v-if="notifications.last_page > 1"
                    class="flex items-center justify-between px-5 py-3 border-t border-base"
                >
                    <button
                        type="button"
                        class="text-sm text-body-1 hover:text-body disabled:opacity-30 disabled:cursor-not-allowed"
                        :disabled="notifications.current_page <= 1"
                        @click="goToPage(notifications.current_page - 1)"
                    >
                        ← {{ $t('Previous') }}
                    </button>
                    <span class="text-xs text-body-1">
                        {{ $t('Page') }} {{ notifications.current_page }} {{ $t('of') }} {{ notifications.last_page }}
                    </span>
                    <button
                        type="button"
                        class="text-sm text-body-1 hover:text-body disabled:opacity-30 disabled:cursor-not-allowed"
                        :disabled="notifications.current_page >= notifications.last_page"
                        @click="goToPage(notifications.current_page + 1)"
                    >
                        {{ $t('Next') }} →
                    </button>
                </footer>
            </div>
        </main>
    </AppLayout>
</template>
