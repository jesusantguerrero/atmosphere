<template>
    <NConfigProvider>
        <AppShell :is-expanded="isExpanded" :nav-class="[!$slots.header && `${panelShadow} border-b`]">
            <template #navigation>
                <!-- Primary Navigation Menu -->
                <div class="px-5 mx-auto sm:pr-6 lg:pr-8 text-body-1/80">
                    <div class="flex items-center justify-between h-16">
                        <div class="flex items-center">
                            <LogerTabButton @click="$emit('back')" v-if="showBackButton">
                               <IconBack />
                            </LogerTabButton>
                            <h4 :class="[showBackButton ? 'lg:ml-2' : 'lg:ml-6']" class="text-lg font-bold">
                                {{ sectionTitle }}
                            </h4>
                        </div>

                        <div class="space-x-2 flex sm:items-center sm:ml-6">
                            <TransactionAddButton class="hidden md:inline-block" />

                            <PrivacyToggle v-model="isPrivacyMode" v-if="!isOnboarding" />

                            <AppNotificationBell
                                :notifications="$page.props.unreadNotifications"
                                @click="$inertia.visit('/notifications')"
                             />

                            <!-- Settings Dropdown -->
                            <div class="relative ml-3">
                                <AppUserMenu
                                    :has-image="$page.props.jetstream.managesProfilePhotos"
                                    :image-url="$page.props.user.profile_photo_url"
                                    :user="$page.props.user"
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
                    item-class="px-5 py-[0.80rem] rounded-md font-bold text-gray-400 w-54 hover:text-primary hover:bg-base-lvl-1"
                    item-active-class="text-primary bg-base-lvl-1/70"
                    is-expandable
                >
                    <template #brand>
                        <div class="flex w-full h-full pl-0 mx-auto mb-0" :class="isExpanded ? 'pl-5' :'justify-center'">
                            <Link href="/dashboard" class="pt-3 mx-auto text-center" v-if="!isExpanded">
                                <img src="/logotype.png" :style="{height: '24px'}" class="mx-auto"/>
                            </Link>
                            <Link href="/dashboard" class="mx-auto text-center " v-else>
                                <AppIcon size="medium" />
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
                <MobileMenuBar :menu="mobileMenu" />
            </template>
        </AppShell>
        <AppGlobals />
    </NConfigProvider>
</template>

<script setup>
    import { provide, ref, computed } from 'vue'
    import { NConfigProvider } from 'naive-ui'
    import { Inertia } from '@inertiajs/inertia'
    import { AtSide } from "atmosphere-ui"
    import { useLocalStorage } from "@vueuse/core"
    import { useI18n } from 'vue-i18n'
    import { Link, usePage } from '@inertiajs/inertia-vue3'

    import JetBanner from '@/Components/atoms/Banner.vue'
    import JetResponsiveNavLink from '@/Components/atoms/ResponsiveNavLink.vue'
    import PrivacyToggle from '@/Components/molecules/PrivacyToggle.vue'
    import LogerTabButton from '@/Components/atoms/LogerTabButton.vue'
    import AppShell from './AppShell.vue'
    import AppIcon from '@/Components/AppIcon.vue'
    import AppGlobals from './AppGlobals.vue'
    import AppNotificationBell from '@/Components/molecules/AppNotificationBell.vue'
    import AppUserMenu from '@/Components/AppUserMenu.vue'
    import MobileMenuBar from '@/Components/mobile/MobileMenuBar.vue'

    import { useAppMenu } from '@/domains/app'
    import { useSelect } from '@/utils/useSelects'
    import TransactionAddButton from './TransactionAddButton.vue'

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
    const { appMenu, headerMenu, mobileMenu } = useAppMenu(t)
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
    });

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

