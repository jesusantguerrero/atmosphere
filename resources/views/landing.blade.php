<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.marketing-head')

    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Loger',
            'description' => __('landing.meta.description'),
            'applicationCategory' => 'FinanceApplication',
            'operatingSystem' => 'Web',
            'url' => url('/'),
            'image' => url('/logo.png'),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
            'author' => [
                '@type' => 'Person',
                'name' => 'Jesus Guerrero',
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body class="bg-gray-950 text-gray-100 antialiased overflow-x-hidden">

    @include('partials.marketing-nav')

    <main class="relative">

        {{-- Ambient gradient glow behind hero --}}
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 overflow-hidden h-[800px]">
            <div class="absolute left-1/2 top-0 -translate-x-1/2 w-[1100px] h-[600px] rounded-full opacity-30 blur-3xl"
                 style="background: radial-gradient(closest-side, rgba(243,126,161,0.55), rgba(243,126,161,0.05) 60%, transparent 70%);"></div>
            <div class="absolute left-1/3 top-40 w-[400px] h-[400px] rounded-full opacity-20 blur-3xl"
                 style="background: radial-gradient(closest-side, rgba(99,102,241,0.45), transparent 70%);"></div>
        </div>

        {{-- ─── HERO ─────────────────────────────────────────── --}}
        <section class="max-w-6xl mx-auto px-6 pt-20 pb-24 lg:pt-28 lg:pb-32">
            <div class="grid lg:grid-cols-12 gap-12 items-center">

                {{-- Left: copy + CTAs --}}
                <div class="lg:col-span-7 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 border border-primary/30 bg-primary/5 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-primary mb-8">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                        {{ __('landing.hero.badge') }}
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-[68px] font-bold leading-[1.05] tracking-tight mb-6 text-white">
                        {{ __('landing.hero.headline_a') }}
                        <span class="block bg-gradient-to-r from-primary via-primary to-primary-dark bg-clip-text text-transparent">
                            {{ __('landing.hero.headline_b') }}
                        </span>
                    </h1>

                    <p class="text-lg sm:text-xl text-gray-400 max-w-xl mx-auto lg:mx-0 mb-10 leading-relaxed">
                        {{ __('landing.hero.subhead') }}
                    </p>

                    <div class="flex flex-col sm:flex-row items-center lg:items-start justify-center lg:justify-start gap-3">
                        <a href="{{ route('register') }}"
                           data-cta="hero-primary"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-4 rounded-xl transition-all duration-200 text-base shadow-xl shadow-primary/25 hover:shadow-primary/40 hover:-translate-y-0.5">
                            {{ __('landing.hero.cta_primary') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                        <a href="https://loger.neatlancer.com"
                           target="_blank"
                           rel="noopener noreferrer"
                           data-cta="hero-secondary"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-gray-700 hover:border-gray-500 text-gray-200 hover:text-white font-medium px-7 py-4 rounded-xl transition-colors text-base">
                            {{ __('landing.hero.cta_secondary') }}
                        </a>
                    </div>

                    <p class="text-xs text-gray-500 mt-6">
                        <svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ __('landing.hero.reassurance') }}
                    </p>
                </div>

                {{-- Right: floating product preview card (Loger budget UI mockup) --}}
                <div class="lg:col-span-5 relative">
                    <div class="relative rounded-2xl border border-gray-800/80 bg-gray-900/70 backdrop-blur-sm shadow-2xl shadow-black/40 p-5 transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">

                        {{-- Mini-app titlebar --}}
                        <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-800">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500/70"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500/70"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/70"></span>
                            </div>
                            <span class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold">{{ __('landing.mockups.hero_titlebar') }}</span>
                        </div>

                        {{-- Ready-to-Assign card --}}
                        <div class="rounded-xl bg-gradient-to-br from-emerald-500/15 to-emerald-500/5 border border-emerald-500/20 p-5 mb-3">
                            <div class="text-3xl font-bold text-white tabular-nums leading-none">$1,000.00</div>
                            <div class="text-xs text-emerald-300/80 mt-1.5">{{ __('landing.mockups.hero_to_budget') }}</div>
                            <button class="mt-4 w-full text-xs font-semibold py-2 rounded-lg bg-emerald-500/90 text-emerald-950 hover:bg-emerald-400 transition-colors">
                                {{ __('landing.mockups.hero_assign_button') }}
                            </button>
                        </div>

                        {{-- Category rows --}}
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-800/40 transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1 h-6 rounded-full bg-primary"></span>
                                    <span class="text-sm text-gray-200">{{ __('landing.mockups.category_savings') }}</span>
                                </div>
                                <span class="text-sm font-medium text-emerald-400 tabular-nums">$500</span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-800/40 transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1 h-6 rounded-full bg-primary"></span>
                                    <span class="text-sm text-gray-200">{{ __('landing.mockups.category_personal') }}</span>
                                </div>
                                <span class="text-sm font-medium text-emerald-400 tabular-nums">$300</span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-800/40 transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1 h-6 rounded-full bg-gray-600"></span>
                                    <span class="text-sm text-gray-200">{{ __('landing.mockups.category_rent') }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-400 tabular-nums">$200</span>
                            </div>
                        </div>
                    </div>

                    {{-- Floating accent card behind --}}
                    <div aria-hidden="true" class="hidden lg:block absolute -bottom-6 -left-6 w-40 h-24 rounded-xl bg-gray-900/80 border border-gray-800 -z-10"></div>
                    <div aria-hidden="true" class="hidden lg:block absolute -top-4 -right-4 px-3 py-2 rounded-lg bg-primary text-white text-[11px] font-semibold shadow-lg shadow-primary/30">
                        {{ __('landing.mockups.hero_floating_chip') }}
                    </div>
                </div>

            </div>

            {{-- Trust strip --}}
            <div class="mt-20 pt-10 border-t border-gray-900 flex flex-col sm:flex-row items-center justify-between gap-6">
                <p class="text-xs uppercase tracking-widest text-gray-600 font-semibold">{{ __('landing.mockups.trust_strip_label') }}</p>
                <div class="flex items-center gap-6 sm:gap-8 text-sm text-gray-500">
                    <span class="flex items-center gap-2">🇩🇴 <span>{{ __('landing.mockups.trust_strip_dr') }}</span></span>
                    <span class="flex items-center gap-2">🌎 <span>{{ __('landing.mockups.trust_strip_anywhere') }}</span></span>
                </div>
            </div>
        </section>

        {{-- ─── PILLARS ──────────────────────────────────────── --}}
        <section class="max-w-6xl mx-auto px-6 pb-32">

            <div class="text-center max-w-2xl mx-auto mb-16">
                <div class="inline-block text-xs uppercase tracking-widest text-primary font-semibold mb-4">{{ __('landing.pillars.eyebrow') }}</div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white tracking-tight mb-4 leading-tight">
                    {{ __('landing.pillars.title') }}
                </h2>
                <p class="text-gray-400 leading-relaxed">{{ __('landing.pillars.subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                {{-- Finance card with embedded chart mockup --}}
                <div class="group relative bg-gray-900/60 border border-gray-800 hover:border-gray-700 rounded-2xl p-6 lg:p-7 flex flex-col transition-colors">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-primary/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-white">{{ __('landing.features.finance.title') }}</h2>
                    </div>

                    {{-- Embedded mini-mockup: net-worth bars --}}
                    <div class="rounded-xl bg-gray-950/60 border border-gray-800/60 p-4 mb-5">
                        <div class="text-[11px] uppercase tracking-wider text-gray-500 mb-1">{{ __('landing.mockups.finance_networth') }}</div>
                        <div class="text-xl font-bold text-white mb-3 tabular-nums">$1,331,211</div>
                        <div class="flex items-end gap-1 h-12">
                            <div class="flex-1 bg-primary/30 rounded-sm" style="height: 30%"></div>
                            <div class="flex-1 bg-primary/40 rounded-sm" style="height: 45%"></div>
                            <div class="flex-1 bg-primary/50 rounded-sm" style="height: 55%"></div>
                            <div class="flex-1 bg-primary/60 rounded-sm" style="height: 70%"></div>
                            <div class="flex-1 bg-primary/70 rounded-sm" style="height: 60%"></div>
                            <div class="flex-1 bg-primary/80 rounded-sm" style="height: 85%"></div>
                            <div class="flex-1 bg-primary rounded-sm" style="height: 100%"></div>
                        </div>
                    </div>

                    <p class="text-gray-400 text-sm leading-relaxed mb-5">
                        {{ __('landing.features.finance.description') }}
                    </p>

                    <ul class="mt-auto space-y-2 text-sm text-gray-300">
                        @foreach ((array) __('landing.features.finance.items') as $item)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Meals card with mini meal list --}}
                <div class="group relative bg-gray-900/60 border border-gray-800 hover:border-gray-700 rounded-2xl p-6 lg:p-7 flex flex-col transition-colors">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-white">{{ __('landing.features.meals.title') }}</h2>
                    </div>

                    {{-- Embedded mini-mockup: weekly meal grid --}}
                    <div class="rounded-xl bg-gray-950/60 border border-gray-800/60 p-4 mb-5 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">{{ __('landing.mockups.meal_monday') }}</span>
                            <span class="text-gray-200">{{ __('landing.mockups.meal_dish_1') }}</span>
                            <span class="text-amber-400/80 tabular-nums">$8</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">{{ __('landing.mockups.meal_tuesday') }}</span>
                            <span class="text-gray-200">{{ __('landing.mockups.meal_dish_2') }}</span>
                            <span class="text-amber-400/80 tabular-nums">$12</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">{{ __('landing.mockups.meal_wednesday') }}</span>
                            <span class="text-gray-200">{{ __('landing.mockups.meal_dish_3') }}</span>
                            <span class="text-amber-400/80 tabular-nums">$10</span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-2 mt-2 border-t border-gray-800/60">
                            <span class="text-gray-500">{{ __('landing.mockups.meal_week_budget') }}</span>
                            <span></span>
                            <span class="text-white font-medium tabular-nums">$84 / $120</span>
                        </div>
                    </div>

                    <p class="text-gray-400 text-sm leading-relaxed mb-5">
                        {{ __('landing.features.meals.description') }}
                    </p>

                    <ul class="mt-auto space-y-2 text-sm text-gray-300">
                        @foreach ((array) __('landing.features.meals.items') as $item)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Housing card with bills mockup --}}
                <div class="group relative bg-gray-900/60 border border-gray-800 hover:border-gray-700 rounded-2xl p-6 lg:p-7 flex flex-col transition-colors">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-white">{{ __('landing.features.housing.title') }}</h2>
                    </div>

                    {{-- Embedded mini-mockup: bill timeline --}}
                    <div class="rounded-xl bg-gray-950/60 border border-gray-800/60 p-4 mb-5 space-y-2.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-300 flex items-center justify-center text-[10px] font-bold">02</div>
                            <div class="flex-1">
                                <div class="text-xs text-gray-200">{{ __('landing.mockups.bill_internet') }}</div>
                                <div class="text-[10px] text-gray-500">{{ __('landing.mockups.bill_internet_meta') }}</div>
                            </div>
                            <span class="text-xs text-gray-300 tabular-nums">$45</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-300 flex items-center justify-center text-[10px] font-bold">15</div>
                            <div class="flex-1">
                                <div class="text-xs text-gray-200">{{ __('landing.mockups.bill_electricity') }}</div>
                                <div class="text-[10px] text-gray-500">{{ __('landing.mockups.bill_electricity_meta') }}</div>
                            </div>
                            <span class="text-xs text-gray-300 tabular-nums">$78</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-300 flex items-center justify-center text-[10px] font-bold">28</div>
                            <div class="flex-1">
                                <div class="text-xs text-gray-200">{{ __('landing.mockups.bill_water') }}</div>
                                <div class="text-[10px] text-gray-500">{{ __('landing.mockups.bill_water_meta') }}</div>
                            </div>
                            <span class="text-xs text-gray-300 tabular-nums">$22</span>
                        </div>
                    </div>

                    <p class="text-gray-400 text-sm leading-relaxed mb-5">
                        {{ __('landing.features.housing.description') }}
                    </p>

                    <ul class="mt-auto space-y-2 text-sm text-gray-300">
                        @foreach ((array) __('landing.features.housing.items') as $item)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-sky-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Bottom row: Family + Calendar (2 wider cards) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">

                {{-- Family card with member roster mockup --}}
                <div class="group relative bg-gray-900/60 border border-gray-800 hover:border-gray-700 rounded-2xl p-6 lg:p-7 flex flex-col transition-colors">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-white">{{ __('landing.features.family.title') }}</h2>
                    </div>

                    {{-- Embedded mini-mockup: family roster --}}
                    <div class="rounded-xl bg-gray-950/60 border border-gray-800/60 p-4 mb-5 space-y-2.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-purple-500/25 text-purple-200 flex items-center justify-center text-xs font-semibold">A</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-200">{{ __('landing.mockups.family_member_1_name') }}</div>
                                <div class="text-[10px] text-gray-500">{{ __('landing.mockups.family_member_1_meta') }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-500/25 text-amber-200 flex items-center justify-center text-xs font-semibold">D</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-200">{{ __('landing.mockups.family_member_2_name') }} <span class="text-[10px] text-gray-500">· {{ __('landing.mockups.family_member_2_age') }}</span></div>
                                <div class="text-[10px] text-gray-500">{{ __('landing.mockups.family_member_2_meta') }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-sky-500/25 text-sky-200 flex items-center justify-center text-xs font-semibold">P</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-200">{{ __('landing.mockups.family_member_3_name') }}</div>
                                <div class="text-[10px] text-gray-500">{{ __('landing.mockups.family_member_3_meta') }}</div>
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-400 text-sm leading-relaxed mb-5">
                        {{ __('landing.features.family.description') }}
                    </p>

                    <ul class="mt-auto space-y-2 text-sm text-gray-300">
                        @foreach ((array) __('landing.features.family.items') as $item)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-purple-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Calendar card showing the integrating layer (multi-color week) --}}
                <div class="group relative bg-gray-900/60 border border-gray-800 hover:border-gray-700 rounded-2xl p-6 lg:p-7 flex flex-col transition-colors">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-white">{{ __('landing.features.calendar.title') }}</h2>
                        <span class="ml-auto text-[10px] uppercase tracking-wider text-emerald-300/80 font-semibold">{{ __('landing.mockups.integrating_layer') }}</span>
                    </div>

                    {{-- Embedded mini-mockup: weekly cross-pillar timeline --}}
                    <div class="rounded-xl bg-gray-950/60 border border-gray-800/60 p-4 mb-5 space-y-2">
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-500 w-7 font-medium">{{ __('landing.mockups.meal_monday') }}</span>
                            <span class="w-1.5 h-4 rounded-full bg-amber-500"></span>
                            <span class="flex-1 text-gray-200">{{ __('landing.mockups.cal_event_food') }}</span>
                            <span class="text-amber-400/80">{{ __('landing.mockups.cal_pillar_food') }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-500 w-7 font-medium">{{ __('landing.mockups.meal_monday') }}</span>
                            <span class="w-1.5 h-4 rounded-full bg-sky-500"></span>
                            <span class="flex-1 text-gray-200">{{ __('landing.mockups.cal_event_home') }}</span>
                            <span class="text-sky-400/80">{{ __('landing.mockups.cal_pillar_home') }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-500 w-7 font-medium">{{ __('landing.mockups.meal_tuesday') }}</span>
                            <span class="w-1.5 h-4 rounded-full bg-primary"></span>
                            <span class="flex-1 text-gray-200">{{ __('landing.mockups.cal_event_finance') }}</span>
                            <span class="text-primary/90">{{ __('landing.mockups.cal_pillar_finance') }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-500 w-7 font-medium">{{ __('landing.mockups.meal_wednesday') }}</span>
                            <span class="w-1.5 h-4 rounded-full bg-purple-500"></span>
                            <span class="flex-1 text-gray-200">{{ __('landing.mockups.cal_event_family') }}</span>
                            <span class="text-purple-400/90">{{ __('landing.mockups.cal_pillar_family') }}</span>
                        </div>
                    </div>

                    <p class="text-gray-400 text-sm leading-relaxed mb-5">
                        {{ __('landing.features.calendar.description') }}
                    </p>

                    <ul class="mt-auto space-y-2 text-sm text-gray-300">
                        @foreach ((array) __('landing.features.calendar.items') as $item)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>
        </section>

        {{-- ─── WHY LOGER (with stat strip) ──────────────────── --}}
        <section class="border-t border-gray-900 bg-gray-950">
            <div class="max-w-6xl mx-auto px-6 py-24 lg:py-32">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="inline-block text-xs uppercase tracking-widest text-primary font-semibold mb-4">{{ __('landing.why.eyebrow') }}</div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-6 tracking-tight">
                            {{ __('landing.why.title') }}
                        </h2>
                        <p class="text-gray-400 leading-relaxed text-lg">
                            {{ __('landing.why.body') }}
                        </p>
                    </div>

                    {{-- Multi-currency mockup --}}
                    <div class="relative">
                        <div class="rounded-2xl bg-gray-900/60 border border-gray-800 p-6">
                            <div class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-4">{{ __('landing.mockups.why_accounts_label') }}</div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-950/60 border border-gray-800/60">
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-lg bg-primary/15 text-primary flex items-center justify-center font-bold text-xs">DOP</span>
                                        <span class="text-sm text-gray-200">{{ __('landing.mockups.why_account_bhd') }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-white tabular-nums">RD$ 45,210</span>
                                </div>
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-950/60 border border-gray-800/60">
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-lg bg-emerald-500/15 text-emerald-400 flex items-center justify-center font-bold text-xs">USD</span>
                                        <span class="text-sm text-gray-200">Wise — Multi</span>
                                    </div>
                                    <span class="text-sm font-semibold text-white tabular-nums">$ 1,820</span>
                                </div>
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-950/60 border border-gray-800/60">
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-lg bg-sky-500/15 text-sky-400 flex items-center justify-center font-bold text-xs">EUR</span>
                                        <span class="text-sm text-gray-200">Revolut</span>
                                    </div>
                                    <span class="text-sm font-semibold text-white tabular-nums">€ 340</span>
                                </div>
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-950/60 border border-gray-800/60">
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-xs">MXN</span>
                                        <span class="text-sm text-gray-200">Banorte</span>
                                    </div>
                                    <span class="text-sm font-semibold text-white tabular-nums">MX$ 8,900</span>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-800/60 flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ __('landing.mockups.why_networth_label') }}</span>
                                <span class="text-base font-bold text-white tabular-nums">$ 3,425</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── FINAL CTA ────────────────────────────────────── --}}
        <section class="relative overflow-hidden">
            <div aria-hidden="true" class="absolute inset-0 -z-10">
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[500px] rounded-full opacity-20 blur-3xl"
                     style="background: radial-gradient(closest-side, rgba(243,126,161,0.7), transparent 70%);"></div>
            </div>
            <div class="max-w-4xl mx-auto px-6 py-28 text-center">
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 tracking-tight leading-tight">
                    {{ __('landing.final_cta.title') }}
                </h2>
                <p class="text-gray-400 text-lg mb-10 max-w-xl mx-auto">
                    {{ __('landing.final_cta.subtitle') }}
                </p>
                <a href="{{ route('register') }}"
                   data-cta="footer-primary"
                   class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold px-10 py-4 rounded-xl transition-all duration-200 text-base shadow-2xl shadow-primary/30 hover:shadow-primary/50 hover:-translate-y-0.5">
                    {{ __('landing.final_cta.cta') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <p class="text-xs text-gray-500 mt-6">{{ __('landing.hero.reassurance') }}</p>
            </div>
        </section>

    </main>

    @include('partials.marketing-footer')

</body>
</html>
