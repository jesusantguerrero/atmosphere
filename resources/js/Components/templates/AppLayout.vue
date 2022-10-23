<template>
    <NConfigProvider>
        <AppShell :is-expanded="isExpanded" :nav-class="[!$slots.header && `${panelShadow} border-b`]">
            <template #navigation>
                <!-- Primary Navigation Menu -->
                <div class="pr-4 mx-auto sm:pr-6 lg:pr-8 text-body-1/80">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center">
                            <LogerTabButton @click="$emit('back')" v-if="showBackButton">
                               <IconBack />
                            </LogerTabButton>
                            <h4 :class="[showBackButton ? 'lg:ml-2' : 'lg:ml-6']" class="text-lg font-bold">
                                {{ sectionTitle }}
                            </h4>
                        </div>

                        <div class="hidden space-x-2 sm:flex sm:items-center sm:ml-6">
                            <AtButton class="flex items-center px-2 space-x-2 text-sm text-white bg-primary" rounded @click="openTransferModal()" v-if="!isOnboarding">
                                <div class="flex items-center justify-center px-1 py-1 rounded-md bg-white/40">
                                    <i class="fa fa-exchange-alt"></i>
                                </div>
                                <span>
                                    Add transaction
                                </span>
                            </AtButton>

                            <PrivacyToggle v-model="isPrivacyMode" v-if="!isOnboarding" />
                            <!-- <span> {{ formatMoney($page.props.balance) }}</span> -->

                            <AppNotificationBell
                                :notifications="$page.props.unreadNotifications"
                                @click="$inertia.visit('/notifications')"
                             />


                            <!-- Settings Dropdown -->
                            <div class="relative ml-3">
                                <JetDropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm transition border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 active:slate-600" >
                                            <img class="object-cover w-8 h-8 rounded-full" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name" />
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 transition border border-transparent rounded-md text-body hover:text-gray-100 hover:bg-base-lvl-1 focus:outline-none focus:bg-base-lvl-2">
                                                {{ $page.props.user.name }}

                                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Manage Account
                                        </div>

                                        <jet-dropdown-link :href="route('profile.show')">
                                            Profile
                                        </jet-dropdown-link>

                                        <jet-dropdown-link :href="route('api-tokens.index')" v-if="$page.props.jetstream.hasApiFeatures">
                                            API Tokens
                                        </jet-dropdown-link>

                                        <div class="border-t border-gray-100"></div>

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <jet-dropdown-link as="button">
                                                Log Out
                                            </jet-dropdown-link>
                                        </form>
                                    </template>
                                </JetDropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="flex items-center -mr-2 sm:hidden">
                            <button @click="showingNavigationDropdown = ! showingNavigationDropdown" class="inline-flex items-center justify-center p-2 text-gray-400 transition rounded-md hover:text-body hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-body">
                                <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
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
                                <img class="object-cover w-10 h-10 rounded-full" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name" />
                            </div>

                            <div>
                                <div class="text-base font-medium text-gray-800">{{ $page.props.user.name }}</div>
                                <div class="text-sm font-medium text-body">{{ $page.props.user.email }}</div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <jet-responsive-nav-link :href="route('profile.show')" :active="route().current('profile.show')">
                                Profile
                            </jet-responsive-nav-link>

                            <jet-responsive-nav-link :href="route('api-tokens.index')" :active="route().current('api-tokens.index')" v-if="$page.props.jetstream.hasApiFeatures">
                                API Tokens
                            </jet-responsive-nav-link>

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <jet-responsive-nav-link as="button">
                                    Log Out
                                </jet-responsive-nav-link>
                            </form>

                            <!-- Team Management -->
                            <template v-if="$page.props.jetstream.hasTeamFeatures && $page.props.user.current_team">
                                <div class="border-t border-gray-200"></div>

                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Manage Team
                                </div>

                                <!-- Team Settings -->
                                <jet-responsive-nav-link :href="route('teams.show', $page.props.user.current_team)" :active="route().current('teams.show')">
                                    Team Settings
                                </jet-responsive-nav-link>

                                <jet-responsive-nav-link :href="route('teams.create')" :active="route().current('teams.create')">
                                    Create New Team
                                </jet-responsive-nav-link>

                                <div class="border-t border-gray-200"></div>

                                <!-- Team Switcher -->
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Switch Teams
                                </div>

                                <template v-for="team in $page.props.user.all_teams" :key="team.id">
                                    <form @submit.prevent="switchToTeam(team)">
                                        <jet-responsive-nav-link as="button">
                                            <div class="flex items-center">
                                                <svg v-if="team.id == $page.props.user.current_team_id" class="w-5 h-5 mr-2 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <div>{{ team.name }}</div>
                                            </div>
                                        </jet-responsive-nav-link>
                                    </form>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template #aside>
                <AtSide
                    class="text-bold bg-base-lvl-3 border-none shadow-none"
                    title="Loger"
                    :class="panelShadow"
                    v-model:isExpanded="isExpanded"
                    :menu="currentMenu"
                    :header-menu="headerMenu"
                    :current-path="currentPath"
                    icon-class="text-gray-400 transition hover:text-primary"
                    item-class="w-54 px-5 py-2 font-bold text-gray-400 text-md hover:text-primary hover:bg-base-lvl-1"
                    item-active-class="text-primary bg-base-lvl-1 border-r-2 border-primary"
                    is-expandable
                >
                    <template #brand>
                        <div class="flex w-full pl-0 mx-auto" :class="isExpanded ? 'pl-5' :'justify-center'">
                            <div class="mx-auto text-center " v-if="!isExpanded">
                                <img src="/logotype.png" :style="{height: '32px'}" class="mx-auto"/>
                            </div>
                            <div class="mx-auto text-center " v-else>
                                <AppIcon />
                            </div>
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
                <article class="overflow-hidden overflow-y-auto ic-scroller">
                    <JetBanner />
                    <slot />
                    <!-- <NavigationBottom :menu-items="menu" /> -->
                </article>
            </template>
        </AppShell>
        <AppGlobals />
    </NConfigProvider>
</template>

<script setup>
    import { provide, ref, computed } from 'vue'
    import { darkTheme, NConfigProvider } from 'naive-ui'
    import { Inertia } from '@inertiajs/inertia'
    import { AtSide, AtButton } from "atmosphere-ui"
    import { useLocalStorage } from "@vueuse/core"
    import { useI18n } from 'vue-i18n'
    import { Link, usePage } from '@inertiajs/inertia-vue3'

    import JetBanner from '@/Jetstream/Banner.vue'
    import JetDropdown from '@/Jetstream/Dropdown.vue'
    import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
    import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink.vue'
    import PrivacyToggle from '@/Components/molecules/PrivacyToggle.vue'
    import LogerTabButton from '@/Components/atoms/LogerTabButton.vue'
    import AppShell from './AppShell.vue'
    import AppIcon from '@/Components/AppIcon.vue'
    import AppGlobals from './AppGlobals.vue'
    import AppNotificationBell from '@/Components/molecules/AppNotificationBell.vue'

    import { useAppMenu } from '@/domains/app'
    import { useSelect } from '@/utils/useSelects'
    import { darkThemeOverrides } from '@/utils/naiveui'
    import { useTransactionModal } from '@/domains/transactions'
    import { formatMoney } from '@/utils'

    const { openTransferModal } = useTransactionModal()
    const props = defineProps({
        title: {
            type: String
        },
        showBackButton: {
            type: Boolean,
        },
        isOnboarding: {
            type: Boolean,
            default: false
        },
    })

    const { t } = useI18n();
    const { appMenu, headerMenu } = useAppMenu(t)
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
    })

    const pageProps = usePage().props
    const sectionTitle = computed(() => {
        return props.title || pageProps.value.sectionTitle
    })

    const isPrivacyMode = useLocalStorage('hasHiddenValues', false)
    provide('hasHiddenValues', isPrivacyMode)

    // routing
    const showingNavigationDropdown = ref(false)
    const switchToTeam = (team) => {
        Inertia.put(route('current-team.update'), {
            'team_id': team.id
        }, {
            preserveState: false
        })
    }

    const teamMenuItems = computed(() => {
        return {
            manageTeam: {
                label: "Manage Team",
                sections: [
                    ["Team Settings", route('teams.show', pageProps.value.user.current_team)],
                    [ "Create New Team", route('teams.create'), pageProps.value.jetstream.canCreateTeams]
                ],
            },
            switchTeams: {
                label: "Switch Teams",
                sections: []
            }
        }
    })

    const currentPath = computed(() => {
        return document?.location?.pathname
    })
    const isExpanded = useLocalStorage('isMenuExpanded', true);
    const logout = () => Inertia.post(route('logout'));

    //  categories
    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(pageProps.value.categories, 'sub_categories', 'categoryOptions');
    transformCategoryOptions(pageProps.value.accounts, 'accounts', 'accountsOptions');

    // useLogerConfig()
</script>

