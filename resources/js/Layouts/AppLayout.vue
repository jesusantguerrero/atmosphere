<template>
    <div>
        <jet-banner />
        <div class="min-h-screen bg-gray-100 home-container">
            <nav class="app-header">
                <!-- Primary Navigation Menu -->
                <div class="px-4 mx-auto sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Navigation Links -->
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            <div class="relative ml-3">
                                <!-- Teams Dropdown -->
                                <jet-dropdown align="right" width="60" v-if="$page.props.jetstream.hasTeamFeatures">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition bg-white border border-transparent rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50">
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
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm transition border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300">
                                            <img class="object-cover w-8 h-8 rounded-full" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name" />
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none">
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
                            <button @click="showingNavigationDropdown = ! showingNavigationDropdown" class="inline-flex items-center justify-center p-2 text-gray-400 transition rounded-md hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500">
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
                                <div class="text-sm font-medium text-gray-500">{{ $page.props.user.email }}</div>
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
                        class="text-gray-700 bg-white app-side"
                        title="Loger"
                        :menu="menu"
                        item-class="block w-full px-5 py-2 font-bold text-gray-400 rounded-md hover:text-pink-400 hover:bg-gray-100"
                        item-active-class="text-pink-500 bg-gray-100"
                    >
                        <template #brand>
                            <h1 class="pl-5 text-3xl font-bold text-pink-500">Loger.</h1>
                        </template>
                    </AtSide>
                </div>

                <div class="app-content__inner ic-scroller">
                    <!-- Page Heading -->
                    <header class="" v-if="$slots.header">
                        <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
                            <slot name="header"></slot>
                        </div>
                    </header>
                    <!-- Page Content -->
                    <main>
                        <slot></slot>
                    </main>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import JetApplicationMark from '@/Jetstream/ApplicationMark'
    import JetBanner from '@/Jetstream/Banner'
    import JetDropdown from '@/Jetstream/Dropdown'
    import JetDropdownLink from '@/Jetstream/DropdownLink'
    import JetNavLink from '@/Jetstream/NavLink'
    import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink'
    import { AtSide, AtSideItem, AtSideItemGroup } from "atmosphere-ui"

    export default {
        components: {
            JetApplicationMark,
            JetBanner,
            JetDropdown,
            JetDropdownLink,
            JetNavLink,
            JetResponsiveNavLink,
            AtSide,
            AtSideItem,
            AtSideItemGroup
        },

        data() {
            return {
                showingNavigationDropdown: false,
                menu: [
                    {
                        icon: 'home',
                        label: 'Home',
                        to: '/dashboard'
                    },
                    {
                        icon: 'far fa-calendar-alt',
                        label: 'Meal Planer',
                        to: '/meal-planner'
                    },
                    {
                        icon: 'fas fa-drumstick-bite',
                        label: 'Meals',
                        to: '/meals'
                    },
                    {
                        icon: 'fas fa-drumstick-bite',
                        label: 'Integrations',
                        to: '/integrations'
                    },
                    {
                        icon: 'fas fa-dollar-sign',
                        label: 'Finance',
                        to: '/finance'
                    },
                    {
                        icon: 'fas fa-heart',
                        label: 'Relationship',
                        to: '/finance'
                    }
                ]
            }
        },

        methods: {
            switchToTeam(team) {
                this.$inertia.put(route('current-team.update'), {
                    'team_id': team.id
                }, {
                    preserveState: false
                })
            },

            logout() {
                this.$inertia.post(route('logout'));
            },
        }
    }
</script>

<style lang="scss">
.home-container {
  position: relative;
  height: 100vh;
}

.app-header {
    width: 100%;
    top: 0;
    position: fixed;
    background: white;
    border-bottom: 1px solid #f4f5f7;
    z-index: 1000;
}

.appside-container {
  padding-right: 0 !important;
  position: fixed;
  display: grid;
  width: 300px;
  height: 100%;
  z-index: 1001;
}

.app-content {
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  background: #f8f8f8 !important;
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

