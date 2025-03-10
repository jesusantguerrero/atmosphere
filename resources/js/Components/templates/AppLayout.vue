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
    import IconBack from '../icons/IconBack.vue'
    import WatchlistButton from './WatchlistButton.vue'

    import { useAppMenu } from '@/domains/app'
    import { useSelect } from '@/utils/useSelects'
    import { useTransactionModal } from '@/domains/transactions'
    import AppProvider from './AppProvider.vue'
    import { useAppContextStore } from '@/store'
    import FinanceWidget from '../SideWidget/FinanceWidget.vue'
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
                            <TransactionAddButton class="hidden mr-4 md:inline-block" v-if="!isOnboarding" />
                            <!-- <TransactionQuickButton class="hidden mr-4 md:inline-block" v-if="!isOnboarding" /> -->
                            <PrivacyToggle v-model="isPrivacyMode" v-if="!isOnboarding" />
                            <AppNotificationBell
                                :notifications="$page.props.unreadNotifications"
                                @click="router.visit('/notifications')"
                             />
                             <!-- <LogerAssistant /> -->
                             <AtTeamSelect
                                v-if="$page.props.auth.user?.all_teams.length && !context.isMobile"
                                :has-team-features="$page.props.jetstream.hasTeamFeatures"
                                :can-create-teams="$page.props.jetstream.canCreateTeams"
                                :current-team="$page.props.auth.user.current_team"
                                :teams="$page.props.auth.user.all_teams"
                                @switch-team="switchToTeam"
                                :full-height="true"
                                @create="router.visit(route('teams.create'))"
                                resource-name="Space"
                            />
                            <!-- Settings Dropdown -->
                            <div class="relative ml-3"  v-if="$page.props.auth.user">
                                <AppUserMenu
                                    :has-image="$page.props.jetstream.managesProfilePhotos"
                                    :image-url="$page.props.auth.user.profile_photo_url"
                                    :user="$page.props.auth.user"
                                    :has-api-features="$page.props.jetstream.hasApiFeatures"
                                    @logout="logout()"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <JetResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </JetResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="flex-shrink-0 mr-3" >
                                <img class="object-cover w-10 h-10 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" />
                            </div>

                            <div>
                                <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                                <div class="text-sm font-medium text-body">{{ $page.props.auth.user.email }}</div>
                            </div>
                        </div>
                    </div>
                </div>
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
                    icon-class="text-gray-400 transition hover:text-primary"
                    item-class="px-5 py-[0.60rem] rounded-md font-bold text-gray-400 w-54 hover:text-primary hover:bg-base-lvl-1 text-xs"
                    item-active-class="text-primary bg-base-lvl-1/70"
                    is-expandable
                >
                    <template #brand>
                        <div class="flex w-full h-full pl-0 mx-auto mb-0" :class="isExpanded ? 'pl-5' :'justify-center'">
                            <Link href="/dashboard" class="pt-3 mx-auto text-center" v-if="!isExpanded">
                                <img src="/logotype.png" :style="{height: '24px'}" class="mx-auto"/>
                            </Link>
                            <Link href="/dashboard" class="mx-auto text-center " v-else>
                                <AppIcon size="medium"  />
                                <sup> {{ $page.props.version }}</sup>
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



