<script setup lang="ts">
    import { provide, ref, computed, onMounted } from 'vue'
    import { router } from '@inertiajs/vue3'
    import { AtSide, AtTeamSelect } from "atmosphere-ui"
    import { useLocalStorage } from "@vueuse/core"
    import { Link, usePage } from '@inertiajs/vue3'

    import JetBanner from '@/Components/atoms/Banner.vue'
    import JetResponsiveNavLink from '@/Components/atoms/ResponsiveNavLink.vue'
    import PrivacyToggle from '@/Components/molecules/PrivacyToggle.vue'
    import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue'
    import AppShell from './AppShell.vue'
    import AppIcon from '@/Components/AppIcon.vue'
    import AppGlobals from './AppGlobals.vue'
    import AppNotificationBell from '@/Components/molecules/AppNotificationBell.vue'
    import AppUserMenu from '@/Components/AppUserMenu.vue'
    import MobileMenuBar from '@/Components/mobile/MobileMenuBar.vue'
    import TransactionAddButton from './TransactionAddButton.vue'
    import AppResourceSearch from './AppResourceSearch.vue'
    import IconBack from '../icons/IconBack.vue'
    import WatchlistButton from './WatchlistButton.vue'

    import { useAppMenu } from '@/domains/app'
    import { useSelect } from '@/utils/useSelects'
    import { useTransactionModal } from '@/domains/transactions'
    import AppProvider from './AppProvider.vue'
    import { useAppContextStore } from '@/store'
    import FinanceWidget from '../SideWidget/FinanceWidget.vue'
    import { useDarkMode } from '@/composables/useDarkMode'
    // import LogerAssistant from '../organisms/logerAssistant.vue'

    const props = defineProps<{
        title: string;
        showBackButton: boolean;
        isOnboarding: boolean;
        user: Record<string, any>
    }>()

    const context = useAppContextStore()
    const pageProps = usePage().props

    const { appMenu, headerMenu, mobileMenu } = useAppMenu(t, pageProps.modules)
    const serverMenu = computed(() => {
        return pageProps.menu
    })
    const currentMenu = computed(() => {
        return props.isOnboarding ? [{
            icon: 'home',
            name: 'onboarding',
            label: t('Setup'),
            to: '/onboarding',
            as: Link
        }, {
            icon: 'users',
            name: 'userProfile',
            label: t('User Profile'),
            to: '/user/profile',
            as: Link
        }] : appMenu
        // serverMenu.value.map(item => ({
        //     label: item.title,
        //     name: item.title,
        //     to: item.url,
        //     as: Link,
        //     icon: item.icon
        // }))
    });


    const sectionTitle = computed(() => {
        return props.title || pageProps.sectionTitle
    })



    const isPrivacyMode = useLocalStorage('hasHiddenValues', false)
    provide('hasHiddenValues', isPrivacyMode)

    // routing
    const showingNavigationDropdown = ref(false)
    const switchToTeam = (team: any) => {
        router.put(route('current-team.update'), {
            'team_id': team.id
        }, {
            preserveState: false
        })
    }

    const currentPath = computed(() => {
        return document?.location?.pathname
    })
    const isExpanded = useLocalStorage('isMenuExpanded', true);
    const isAsideExpanded = useLocalStorage('isAsideExpanded', false);
    const logout = () => router.post(route('logout'));

    //  categories
    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(pageProps.categories, 'sub_categories', 'categoryOptions');
    transformCategoryOptions(pageProps.accounts, 'accounts', 'accountsOptions');

    const accounts = computed(() => {
        return pageProps.accounts;
    })

    // useLogerConfig()
    const { openTransactionModal } = useTransactionModal()
    const { isDark, toggle: toggleDark } = useDarkMode()
    const handleActions = (action) => {
        const actions = {
            'openTransactionModal': {
                handler: openTransactionModal
            }
        }

        actions[action]?.handler()
    }
</script>

<template>
    <AppProvider>
        <AppShell
            :is-expanded="isExpanded"
            :nav-class="[!$slots.header && `${panelShadow} border-b`]"
            :is-aside-expanded="isAsideExpanded"
        >
            <template #navigation>
                <!-- Primary Navigation Menu -->
                <div class="px-2 mx-auto sm:pr-6 lg:pr-8 text-body-1/80">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center">
                            <LogerButtonTab @click="$emit('back')" v-if="showBackButton">
                               <IconBack />
                            </LogerButtonTab>
                            <slot name="title">
                                <h4 :class="[showBackButton ? 'lg:ml-2' : 'lg:ml-6']" class="text-xs font-bold">
                                    {{ sectionTitle }}
                                </h4>
                            </slot>
                        </div>

                        <div class="flex space-x-2 sm:items-center sm:ml-6">
                            <AppResourceSearch class="hidden mr-2 md:block" v-if="!isOnboarding" />
                            <TransactionAddButton class="hidden mr-4 md:inline-block" v-if="!isOnboarding" />
                            <!-- <TransactionQuickButton class="hidden mr-4 md:inline-block" v-if="!isOnboarding" /> -->
                            <button
                                v-if="!isOnboarding"
                                type="button"
                                @click="toggleDark"
                                :title="isDark ? 'Light mode' : 'Dark mode'"
                                class="px-1 transition-colors text-body-1/60 hover:text-body-1"
                            >
                                <IMdiWeatherSunny v-if="isDark" />
                                <IMdiWeatherNight v-else />
                            </button>
                            <PrivacyToggle v-model="isPrivacyMode" v-if="!isOnboarding" />
                            <AppNotificationBell
                                :notifications="$page.props.unreadNotifications"
                                @click="router.visit('/notifications')"
                             />
                             <!-- <LogerAssistant /> -->
                             <div class="team-select-fix flex items-center" v-if="$page.props.auth.user?.all_teams.length && !context.isMobile">
                             <AtTeamSelect
                                :has-team-features="$page.props.jetstream.hasTeamFeatures"
                                :can-create-teams="$page.props.jetstream.canCreateTeams"
                                :current-team="$page.props.auth.user.current_team"
                                :teams="$page.props.auth.user.all_teams"
                                @switch-team="switchToTeam"
                                :full-height="true"
                                @create="router.visit(route('teams.create'))"
                                resource-name="Space"
                            />
                             </div>
                            <!-- Settings Dropdown (desktop) -->
                            <div class="relative ml-3 hidden lg:block"  v-if="$page.props.auth.user">
                                <AppUserMenu
                                    :has-image="$page.props.jetstream.managesProfilePhotos"
                                    :image-url="$page.props.auth.user.profile_photo_url"
                                    :user="$page.props.auth.user"
                                    :has-api-features="$page.props.jetstream.hasApiFeatures"
                                    @logout="logout()"
                                />
                            </div>
                            <!-- Mobile hamburger (replaces user menu on small screens — opens full nav drawer) -->
                            <button
                                v-if="$page.props.auth.user"
                                type="button"
                                class="lg:hidden flex items-center justify-center w-10 h-10 rounded-full text-body-1 hover:bg-base-lvl-2 transition-colors"
                                :aria-label="$t('Open menu')"
                                aria-haspopup="dialog"
                                :aria-expanded="showingNavigationDropdown"
                                @click="showingNavigationDropdown = true"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ─── Mobile nav drawer ───────────────────────────────
                     Slide-in panel from the right with everything that's
                     not in the bottom-nav: Dashboard, Trends, Housing,
                     Family + About, Help, Settings, Sign out.
                     Bottom-nav stays as-is for the 4 daily-use pillars. -->
                <Teleport to="body" v-if="$page.props.auth.user">
                    <!-- Backdrop -->
                    <transition
                        enter-active-class="transition-opacity duration-200"
                        enter-from-class="opacity-0"
                        leave-active-class="transition-opacity duration-200"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="showingNavigationDropdown"
                            class="fixed inset-0 bg-black/40 z-[1500] lg:hidden"
                            @click="showingNavigationDropdown = false"
                        />
                    </transition>
                    <!-- Panel -->
                    <transition
                        enter-active-class="transition-transform duration-200 ease-out"
                        enter-from-class="translate-x-full"
                        leave-active-class="transition-transform duration-200 ease-in"
                        leave-to-class="translate-x-full"
                    >
                        <aside
                            v-if="showingNavigationDropdown"
                            role="dialog"
                            aria-label="Navigation"
                            class="fixed top-0 right-0 bottom-0 w-72 max-w-[85vw] bg-base-lvl-3 shadow-2xl z-[1501] lg:hidden flex flex-col overflow-y-auto"
                        >
                            <!-- Header: user identity + close -->
                            <header class="flex items-center justify-between px-4 py-4 border-b border-base-lvl-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img
                                        v-if="$page.props.jetstream.managesProfilePhotos && $page.props.auth.user.profile_photo_url"
                                        :src="$page.props.auth.user.profile_photo_url"
                                        class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                                        :alt="$page.props.auth.user.name"
                                    >
                                    <div v-else class="w-10 h-10 rounded-full bg-primary/15 text-primary flex items-center justify-center font-semibold flex-shrink-0">
                                        {{ ($page.props.auth.user.name || '?').slice(0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-body truncate">{{ $page.props.auth.user.name }}</div>
                                        <div class="text-xs text-body-1/60 truncate">{{ $page.props.auth.user.email }}</div>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="ml-2 flex-shrink-0 w-8 h-8 rounded-full text-body-1/60 hover:bg-base-lvl-2 transition-colors flex items-center justify-center"
                                    :aria-label="$t('Close menu')"
                                    @click="showingNavigationDropdown = false"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </header>

                            <!-- All pillars (full list, including the ones hidden from the bottom-nav) -->
                            <nav class="flex-1 py-2">
                                <ul class="space-y-1 px-2">
                                    <li v-for="item in currentMenu" :key="item.to">
                                        <Link
                                            :href="item.to"
                                            class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-body-1 hover:bg-base-lvl-2 hover:text-primary transition-colors"
                                            :class="{ 'bg-primary/10 text-primary': currentPath?.startsWith(item.to) }"
                                            @click="showingNavigationDropdown = false"
                                        >
                                            <i v-if="typeof item.icon === 'string'" :class="item.icon" class="w-5 text-center text-body-1/60"></i>
                                            <span>{{ item.label }}</span>
                                        </Link>
                                    </li>
                                </ul>

                                <!-- Header items: About, Help Center, Settings -->
                                <div class="mt-4 pt-4 border-t border-base-lvl-2">
                                    <ul class="space-y-1 px-2">
                                        <li>
                                            <Link
                                                href="/notifications"
                                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-body-1 hover:bg-base-lvl-2 hover:text-primary transition-colors"
                                                @click="showingNavigationDropdown = false"
                                            >
                                                <i class="fa fa-bell w-5 text-center text-body-1/60"></i>
                                                <span>{{ $t('Notifications') }}</span>
                                                <span v-if="$page.props.unreadNotifications > 0" class="ml-auto text-xs bg-primary text-white rounded-full px-2 py-0.5">{{ $page.props.unreadNotifications }}</span>
                                            </Link>
                                        </li>
                                        <li v-for="item in headerMenu" :key="item.to">
                                            <Link
                                                :href="item.to"
                                                class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-body-1 hover:bg-base-lvl-2 hover:text-primary transition-colors"
                                                @click="showingNavigationDropdown = false"
                                            >
                                                <i :class="item.icon" class="w-5 text-center text-body-1/60"></i>
                                                <span>{{ item.label }}</span>
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </nav>

                            <!-- Sign out anchored at bottom -->
                            <footer class="px-2 py-3 border-t border-base-lvl-2">
                                <button
                                    type="button"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-body-1 hover:bg-base-lvl-2 hover:text-red-500 transition-colors"
                                    @click="logout(); showingNavigationDropdown = false"
                                >
                                    <i class="fa fa-sign-out-alt w-5 text-center text-body-1/60"></i>
                                    <span>{{ $t('Sign out') }}</span>
                                </button>
                            </footer>
                        </aside>
                    </transition>
                </Teleport>
            </template>

            <template #aside>
                <AtSide
                    class="border-none shadow-none text-bold bg-base-lvl-3"
                    title="Loger"
                    :class="panelShadow"
                    v-model:isExpanded="isExpanded"
                    :menu="currentMenu"
                    :header-menu="headerMenu"
                    :current-path="currentPath"
                    brand-container-class="py-2"
                    nav-container-class="px-2 pt-1 space-y-2 border-t"
                    icon-class="text-body-1/70 transition hover:text-primary"
                    item-class="px-5 py-[0.60rem] rounded-md font-bold text-body-1/70 w-54 hover:text-primary hover:bg-base-lvl-1 text-xs"
                    item-active-class="text-primary bg-base-lvl-1/70"
                    is-expandable
                >
                    <template #brand>
                        <div class="flex w-full h-full pl-0 mx-auto mb-0" :class="isExpanded ? 'pl-5' :'justify-center'">
                            <Link href="/dashboard" class="pt-3 mx-auto text-center" v-if="!isExpanded">
                                <img src="/logotype.png" :style="{height: '24px'}" class="mx-auto dark:hidden"/>
                                <img src="/logotype-dark.png" :style="{height: '24px'}" class="mx-auto hidden dark:block"/>
                            </Link>
                            <Link href="/dashboard" class="mx-auto text-center " v-else>
                                <AppIcon size="medium"  />
                                <sup
                                    v-if="$page.props.environment !== 'production'"
                                    class="text-[10px] text-body-1/40 font-mono"
                                    :title="$page.props.version"
                                > {{ $page.props.version }}</sup>
                            </Link>
                        </div>
                    </template>
                </AtSide>
            </template>

            <template #main-section>
                <!-- Page Heading -->
                <header v-if="$slots.header" :class="[isExpanded ? 'lg:pr-56' : 'lg:pr-20', panelShadow]" class="fixed z-30 w-full overflow-hidden border-b bg-base-lvl-3 base-deep-1">
                    <slot name="header" />
                </header>
                <!-- Page Content -->
                <main class="overflow-hidden overflow-y-auto ic-scroller">
                    <JetBanner active-class="mt-14" />
                    <slot />
                </main>
                <MobileMenuBar :menu="mobileMenu" @action="handleActions" />
            </template>

            <template #aside-widget>
                <FinanceWidget
                    v-model:is-expanded="isAsideExpanded"
                    :show-assistant-button="true"
                    :accounts="accounts"
                    :style="''"
                    :token="'abcde'"
                    :user="{}"
                />
            </template>
        </AppShell>
        <AppGlobals />
    </AppProvider>
</template>



