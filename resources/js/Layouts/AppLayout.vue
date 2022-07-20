<template>
    <NConfigProvider :theme="darkTheme" :theme-overrides="darkThemeOverrides">
        <main>
            <JetBanner />
            <div class="min-h-screen dark:bg-slate-800 home-container">
                <nav class="border-b shadow-md dark:border-slate-900 app-header dark:bg-slate-800">
                    <!-- Primary Navigation Menu -->
                    <div class="pr-4 mx-auto sm:pr-6 lg:pr-8 text-white">
                        <div class="flex justify-between h-16 items-center">
                            <div class="flex items-center">
                                <LogerTabButton @click="$emit('back')" v-if="showBackButton">
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--ic" width="32" height="32" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M14.71 6.71a.996.996 0 0 0-1.41 0L8.71 11.3a.996.996 0 0 0 0 1.41l4.59 4.59a.996.996 0 1 0 1.41-1.41L10.83 12l3.88-3.88c.39-.39.38-1.03 0-1.41z"></path></svg>
                                </LogerTabButton>
                                <h4 :class="[showBackButton ? 'ml-2' : 'ml-6']">
                                    {{ sectionTitle }}
                                </h4>
                            </div>

                            <div class="hidden sm:flex sm:items-center sm:ml-6">
                                <AtButton class="text-sm text-white bg-pink-400" rounded @click="openTransferModal()">
                                    <i class="fa fa-exchange-alt"></i>
                                    Add transaction
                                </AtButton>
                                <PrivacyToggle v-model="isPrivacyMode" />

                                <!-- Teams Dropdown -->
                                <div class="relative ml-3">
                                    <jet-dropdown align="right" width="60" v-if="$page.props.jetstream.hasTeamFeatures">
                                        <template #trigger>
                                            <span class="inline-flex rounded-md">
                                                <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-200 transition border border-transparent rounded-md hover:bg-slate-500 hover:text-gray-100 focus:outline-none focus:bg-slate-600">
                                                    {{ $page.props.user.current_team.name }}

                                                    <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <div class="w-60">
                                                <!-- Team Management -->
                                                <template v-if="$page.props.jetstream.hasTeamFeatures">
                                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                                        Manage Team
                                                    </div>

                                                    <!-- Team Settings -->
                                                    <jet-dropdown-link :href="route('teams.show', $page.props.user.current_team)">
                                                        Team Settings
                                                    </jet-dropdown-link>

                                                    <jet-dropdown-link :href="route('teams.create')" v-if="$page.props.jetstream.canCreateTeams">
                                                        Create New Team
                                                    </jet-dropdown-link>

                                                    <div class="border-t border-gray-100"></div>

                                                    <!-- Team Switcher -->
                                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                                        Switch Teams
                                                    </div>

                                                    <template v-for="team in $page.props.user.all_teams" :key="team.id">
                                                        <form @submit.prevent="switchToTeam(team)">
                                                            <jet-dropdown-link as="button">
                                                                <div class="flex items-center">
                                                                    <svg v-if="team.id == $page.props.user.current_team_id" class="w-5 h-5 mr-2 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                    <div>{{ team.name }}</div>
                                                                </div>
                                                            </jet-dropdown-link>
                                                        </form>
                                                    </template>
                                                </template>
                                            </div>
                                        </template>
                                    </jet-dropdown>
                                </div>

                                <!-- Settings Dropdown -->
                                <div class="relative ml-3">
                                    <jet-dropdown align="right" width="48">
                                        <template #trigger>
                                            <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm transition border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 active:slate-600" >
                                                <img class="object-cover w-8 h-8 rounded-full" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name" />
                                            </button>

                                            <span v-else class="inline-flex rounded-md">
                                                <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-200 transition border border-transparent rounded-md hover:text-gray-100 hover:bg-slate-600 focus:outline-none focus:bg-slate-500">
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
                                    </jet-dropdown>
                                </div>
                            </div>

                            <!-- Hamburger -->
                            <div class="flex items-center -mr-2 sm:hidden">
                                <button @click="showingNavigationDropdown = ! showingNavigationDropdown" class="inline-flex items-center justify-center p-2 text-gray-400 transition rounded-md hover:text-gray-200 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-200">
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
                            <jet-responsive-nav-link :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </jet-responsive-nav-link>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="pt-4 pb-1 border-t border-gray-200">
                            <div class="flex items-center px-4">
                                <div v-if="$page.props.jetstream.managesProfilePhotos" class="flex-shrink-0 mr-3" >
                                    <img class="object-cover w-10 h-10 rounded-full" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name" />
                                </div>

                                <div>
                                    <div class="text-base font-medium text-gray-800">{{ $page.props.user.name }}</div>
                                    <div class="text-sm font-medium text-gray-200">{{ $page.props.user.email }}</div>
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
                                <template v-if="$page.props.jetstream.hasTeamFeatures">
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
                </nav>
                <div class="app-content">
                    <div class="appside-container">
                        <AtSide
                            class="dark:text-gray-200 border-r shadow-md dark:border-slate-900 dark:bg-slate-800 app-side"
                            title="Loger"
                            :menu="appMenu"
                            :current-path="currentPath"
                            item-class="block w-full px-5 py-1 mb-2 text-md font-bold text-gray-400 rounded-md hover:text-pink-500 hover:bg-slate-600/50"
                            item-active-class="text-pink-500 bg-slate-600/75"
                        >
                            <template #brand>
                                <h1 class="pl-5 text-3xl font-bold text-pink-500">Loger.</h1>
                            </template>
                        </AtSide>
                    </div>

                    <div class="app-content__inner ic-scroller">
                        <!-- Page Heading -->
                        <header v-if="$slots.header" class="mb-8  w-full bg-slate-600/20 border-b border-slate-400">
                            <slot name="header"></slot>
                        </header>
                        <!-- Page Content -->
                        <main class="overflow-hidden overflow-y-auto">
                            <slot></slot>
                            <!-- <NavigationBottom :menu-items="menu" /> -->
                        </main>
                    </div>
                </div>
            </div>
        </main>
    </NConfigProvider>
</template>

<script setup>
    import { provide, ref, computed } from 'vue'
    import { Inertia } from '@inertiajs/inertia'
    import { AtSide, AtButton } from "atmosphere-ui"
    import JetBanner from '@/Jetstream/Banner.vue'
    import JetDropdown from '@/Jetstream/Dropdown.vue'
    import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
    import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink.vue'
    import { darkTheme, NConfigProvider } from 'naive-ui'
    import PrivacyToggle from '@/Components/molecules/PrivacyToggle.vue'
    import { darkThemeOverrides } from '@/utils/naiveui'
    import { appMenu } from '@/domains/app'
    import { usePage } from '@inertiajs/inertia-vue3'
    import LogerTabButton from '@/Components/atoms/LogerTabButton.vue'
    import { useSelect } from '@/utils/useSelects'
    import { useTransferModal } from '@/utils/useTransferModal'


    const { openTransferModal } = useTransferModal()
    const props = defineProps({
        title: {
            type: String
        },
        showBackButton: {
            type: Boolean,
        }
    })

    const pageProps = usePage().props
    const sectionTitle = computed(() => {
        return props.title || pageProps.value.sectionTitle
    })

    const isPrivacyMode = ref(false)
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

    const currentPath = computed(() => {
        return document?.location?.pathname
    })

    const logout = () => Inertia.post(route('logout'));

    //  categories
    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(pageProps.value.categories, 'sub_categories', 'categoryOptions');
    transformCategoryOptions(pageProps.value.accounts, 'accounts', 'accountsOptions');
</script>

<style lang="scss">
:root {
    --app-side-width: 230px
}
body, html {
    background-color: #1E293B;
}
.home-container {
  position: relative;
  height: 100vh;
}

.app-header {
    width: 100%;
    top: 0;
    position: fixed;
    z-index: 1000;
    padding-left: var(--app-side-width);
}

.appside-container {
  padding-right: 0 !important;
  position: fixed;
  display: grid;
  width: var(--app-side-width);
  height: 100%;
  z-index: 1001;
}

.app-content {
  display: grid;
  grid-template-columns: var(--app-side-width) minmax(0, 1fr);
  position: relative;
  height: 100vh;

  &__inner {
    width: 100%;
    grid-column-start: 2;
    padding: 65px 0;
    padding-bottom: 0;
    position: relative;
    max-height: 100%;
    transition: all ease 0.3s;

    &.header-replacer-mode {
      padding-top: 0;

      .header-replacer {
        height: 73px;
        margin: 0;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        width: 100%;
        z-index: 1000;
        background: white;
        align-items: center;
        padding: 0 10px;
      }

      .section-body {
        padding-top: 140px;
      }
    }
  }
}

.splash-screen {
  background: dodgerblue;
  width: 100%;
  height: 100vh;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media screen and (max-width: 992px) {
  .appside-container {
    z-index: 999;
    width: 256px;
    left: -260px;
    transition: all ease 0.3s;
  }

  .app-content {
    height: auto;
    &__inner {
        grid-column-start: 1;
        grid-column-end: 3;
        padding-bottom: 40px;
    }
  }

  .home-container.menu-expanded {
    .appside-container {
      left: 0;
    }
  }
}

@media print {
  .appside-container,
  .no-print,
  button {
    display: none;
  }

  table {
    width: 100% !important;
    overflow: hidden;
  }

  th td {
    overflow: hidden;
  }

  .app-content {
    grid-column-start: 1;
    grid-column-end: 3;
  }
}


.ic-scroller {
    &::-webkit-scrollbar-thumb {
        background-color: transparentize($color: #000000, $amount: 0.7);
        border-radius: 4px;

        &:hover {
            background-color: transparentize($color: #000000, $amount: 0.7);
        }
    }

    &::-webkit-scrollbar {
        background-color: transparent;
        width: 8px;
        height: 10px;
    }

    &-slim {
        transition: all ease .3s;
        &::-webkit-scrollbar {
            height: 0;
        }

        &:hover {
            &::-webkit-scrollbar {
                height: 3px;
            }
        }
    }
}
</style>

