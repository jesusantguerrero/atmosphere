<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.marketing-head', [
        'pageTitle' => __('landing.open_source.meta.title'),
        'pageDescription' => __('landing.open_source.meta.description'),
        'ogTitle' => __('landing.open_source.meta.title'),
        'ogDescription' => __('landing.open_source.meta.description'),
    ])
</head>
<body class="bg-gray-950 text-gray-100 antialiased">

    @include('partials.marketing-nav')

    <main>
        {{-- ─── HERO (dark) ──────────────────────────────────── --}}
        <section class="relative overflow-hidden">
            <div class="max-w-6xl mx-auto px-6 py-20 lg:py-28">
                <div class="max-w-3xl">
                    <div class="inline-block text-xs uppercase tracking-widest text-primary font-semibold mb-5">
                        {{ __('landing.open_source.hero.eyebrow') }}
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-white leading-[1.1] mb-6">
                        {{ __('landing.open_source.hero.title') }}
                    </h1>
                    <p class="text-lg text-gray-300 leading-relaxed mb-3">
                        {{ __('landing.open_source.hero.subhead_1') }}
                    </p>
                    <p class="text-lg text-gray-400 leading-relaxed mb-10">
                        {{ __('landing.open_source.hero.subhead_2') }}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('register') }}"
                           data-cta="open-source-hero-signup"
                           class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold px-6 py-3.5 rounded-xl shadow-lg shadow-primary/30 transition-colors text-sm">
                            {{ __('landing.open_source.hero.cta_signup') }}
                        </a>
                        <a href="https://github.com/jesusantguerrero/atmosphere"
                           target="_blank"
                           rel="noopener noreferrer"
                           data-cta="open-source-hero-github"
                           class="inline-flex items-center justify-center gap-2 border border-gray-700 hover:border-gray-500 hover:bg-gray-900 text-gray-100 font-semibold px-6 py-3.5 rounded-xl transition-colors text-sm">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.07 3.29 9.37 7.86 10.89.58.11.79-.25.79-.56v-2.16c-3.2.69-3.88-1.37-3.88-1.37-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.75 2.68 1.24 3.34.95.1-.74.4-1.24.72-1.53-2.55-.29-5.24-1.28-5.24-5.71 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.18a10.93 10.93 0 0 1 5.74 0c2.19-1.49 3.15-1.18 3.15-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.44-2.7 5.41-5.27 5.7.41.36.78 1.06.78 2.13v3.16c0 .31.21.67.8.56 4.56-1.52 7.85-5.82 7.85-10.89C23.5 5.65 18.35.5 12 .5z"/></svg>
                            {{ __('landing.open_source.hero.cta_github') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── WHY THE SPLIT (light) ───────────────────────── --}}
        <section class="bg-gray-50 border-y border-gray-200">
            <div class="max-w-6xl mx-auto px-6 py-20 lg:py-24">
                <div class="max-w-2xl mb-12">
                    <div class="inline-block text-xs uppercase tracking-widest text-primary font-semibold mb-3">
                        {{ __('landing.open_source.split.eyebrow') }}
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight mb-3 leading-tight">
                        {{ __('landing.open_source.split.title') }}
                    </h2>
                    <p class="text-gray-600 leading-relaxed">
                        {{ __('landing.open_source.split.subtitle') }}
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-5">
                    {{-- Engine --}}
                    <div class="bg-white border border-gray-200 rounded-2xl p-7 flex flex-col">
                        <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-sky-700 bg-sky-50 border border-sky-100 rounded-full px-2.5 py-1 mb-4 self-start">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                            {{ __('landing.open_source.split.engine.tag') }}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.open_source.split.engine.title') }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ __('landing.open_source.split.engine.body') }}</p>
                    </div>
                    {{-- Distribution --}}
                    <div class="bg-white border border-gray-200 rounded-2xl p-7 flex flex-col">
                        <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary bg-primary-light border border-primary/20 rounded-full px-2.5 py-1 mb-4 self-start">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            {{ __('landing.open_source.split.distribution.tag') }}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.open_source.split.distribution.title') }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ __('landing.open_source.split.distribution.body') }}</p>
                    </div>
                    {{-- Same code --}}
                    <div class="bg-white border border-gray-200 rounded-2xl p-7 flex flex-col">
                        <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1 mb-4 self-start">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            {{ __('landing.open_source.split.same_code.tag') }}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.open_source.split.same_code.title') }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ __('landing.open_source.split.same_code.body') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── WHEN TO SELF-HOST VS USE LOGER (dark) ───────── --}}
        <section class="bg-gray-950">
            <div class="max-w-6xl mx-auto px-6 py-20 lg:py-24">
                <div class="max-w-2xl mb-12">
                    <div class="inline-block text-xs uppercase tracking-widest text-primary font-semibold mb-3">
                        {{ __('landing.open_source.when.eyebrow') }}
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight leading-tight">
                        {{ __('landing.open_source.when.title') }}
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    {{-- Self-host --}}
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-7">
                        <h3 class="text-lg font-bold text-white mb-5">{{ __('landing.open_source.when.self_host.title') }}</h3>
                        <ul class="space-y-3 text-sm text-gray-300">
                            @foreach ((array) __('landing.open_source.when.self_host.items') as $item)
                                <li class="flex items-start gap-3">
                                    <svg class="w-4 h-4 text-sky-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span class="leading-relaxed">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    {{-- Hosted --}}
                    <div class="bg-gradient-to-b from-primary/10 to-gray-900 border border-primary/30 rounded-2xl p-7">
                        <h3 class="text-lg font-bold text-white mb-5">{{ __('landing.open_source.when.hosted.title') }}</h3>
                        <ul class="space-y-3 text-sm text-gray-300">
                            @foreach ((array) __('landing.open_source.when.hosted.items') as $item)
                                <li class="flex items-start gap-3">
                                    <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span class="leading-relaxed">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <p class="text-center text-sm text-gray-500 mt-10 max-w-xl mx-auto">
                    {{ __('landing.open_source.when.note') }}
                </p>
            </div>
        </section>

        {{-- ─── HOW TO GET STARTED (light) ──────────────────── --}}
        <section class="bg-white border-y border-gray-200">
            <div class="max-w-6xl mx-auto px-6 py-20 lg:py-24">
                <div class="max-w-2xl mb-12">
                    <div class="inline-block text-xs uppercase tracking-widest text-primary font-semibold mb-3">
                        {{ __('landing.open_source.getting_started.eyebrow') }}
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight leading-tight">
                        {{ __('landing.open_source.getting_started.title') }}
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    {{-- Self-host card --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-7 flex flex-col">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.open_source.getting_started.self_host.title') }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed mb-5">{{ __('landing.open_source.getting_started.self_host.body') }}</p>
                        <pre class="bg-gray-900 text-gray-100 text-xs font-mono rounded-lg px-4 py-3 mb-5 overflow-x-auto"><code>{{ __('landing.open_source.getting_started.self_host.code') }}</code></pre>
                        <a href="https://github.com/jesusantguerrero/atmosphere"
                           target="_blank"
                           rel="noopener noreferrer"
                           data-cta="open-source-getstarted-github"
                           class="inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white font-semibold px-5 py-3 rounded-xl transition-colors text-sm mt-auto">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.07 3.29 9.37 7.86 10.89.58.11.79-.25.79-.56v-2.16c-3.2.69-3.88-1.37-3.88-1.37-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.75 2.68 1.24 3.34.95.1-.74.4-1.24.72-1.53-2.55-.29-5.24-1.28-5.24-5.71 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.18a10.93 10.93 0 0 1 5.74 0c2.19-1.49 3.15-1.18 3.15-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.44-2.7 5.41-5.27 5.7.41.36.78 1.06.78 2.13v3.16c0 .31.21.67.8.56 4.56-1.52 7.85-5.82 7.85-10.89C23.5 5.65 18.35.5 12 .5z"/></svg>
                            {{ __('landing.open_source.getting_started.self_host.cta') }}
                        </a>
                    </div>
                    {{-- Hosted card --}}
                    <div class="bg-gradient-to-b from-primary-light to-white border border-primary/30 rounded-2xl p-7 flex flex-col">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.open_source.getting_started.hosted.title') }}</h3>
                        <p class="text-sm text-gray-700 leading-relaxed mb-5">{{ __('landing.open_source.getting_started.hosted.body') }}</p>
                        <a href="{{ route('register') }}"
                           data-cta="open-source-getstarted-signup"
                           class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold px-5 py-3 rounded-xl shadow-lg shadow-primary/30 transition-colors text-sm mt-auto">
                            {{ __('landing.open_source.getting_started.hosted.cta') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── CLOSING (dark) ──────────────────────────────── --}}
        <section class="bg-gray-950">
            <div class="max-w-4xl mx-auto px-6 py-20 lg:py-24 text-center">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight leading-tight mb-7">
                    {{ __('landing.open_source.closing.title') }}
                </h2>
                <a href="{{ route('register') }}"
                   data-cta="open-source-closing-signup"
                   class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-semibold px-6 py-3.5 rounded-xl shadow-lg shadow-primary/30 transition-colors text-sm">
                    {{ __('landing.open_source.closing.cta') }}
                </a>
            </div>
        </section>
    </main>

    @include('partials.marketing-footer')

</body>
</html>
