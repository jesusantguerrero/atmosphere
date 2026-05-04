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
<body class="bg-gray-950 text-gray-100 antialiased">

    @include('partials.marketing-nav')

    <main>
        {{-- Hero --}}
        <section class="max-w-6xl mx-auto px-6 pt-24 pb-20 text-center">
            <div class="inline-flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-full px-4 py-1.5 text-sm text-gray-400 mb-8">
                <span class="w-2 h-2 rounded-full bg-primary inline-block"></span>
                {{ __('landing.hero.badge') }}
            </div>

            <h1 class="text-5xl sm:text-6xl font-bold leading-tight tracking-tight mb-6">
                {{ __('landing.hero.headline_a') }}<br>
                <span class="text-primary">{{ __('landing.hero.headline_b') }}</span>
            </h1>

            <p class="text-lg sm:text-xl text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                {{ __('landing.hero.subhead') }}
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   data-cta="hero-primary"
                   class="w-full sm:w-auto inline-block bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3.5 rounded-lg transition-colors text-base shadow-lg shadow-primary/20">
                    {{ __('landing.hero.cta_primary') }}
                </a>
                <a href="https://loger.neatlancer.com"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-cta="hero-secondary"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 text-gray-300 hover:text-white font-medium px-4 py-3.5 transition-colors text-base">
                    {{ __('landing.hero.cta_secondary') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>

            <p class="text-xs text-gray-500 mt-6">{{ __('landing.hero.reassurance') }}</p>
        </section>

        {{-- Feature Grid --}}
        <section class="max-w-6xl mx-auto px-6 pb-24">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Finance --}}
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 flex flex-col gap-5">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white mb-2">{{ __('landing.features.finance.title') }}</h2>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ __('landing.features.finance.description') }}</p>
                    </div>
                    <ul class="mt-auto space-y-2 text-sm text-gray-500">
                        @foreach ((array) __('landing.features.finance.items') as $item)
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Meal Planning --}}
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 flex flex-col gap-5">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white mb-2">{{ __('landing.features.meals.title') }}</h2>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ __('landing.features.meals.description') }}</p>
                    </div>
                    <ul class="mt-auto space-y-2 text-sm text-gray-500">
                        @foreach ((array) __('landing.features.meals.items') as $item)
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Housing --}}
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 flex flex-col gap-5">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 22V12h6v10"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white mb-2">{{ __('landing.features.housing.title') }}</h2>
                        <p class="text-gray-400 text-sm leading-relaxed">{{ __('landing.features.housing.description') }}</p>
                    </div>
                    <ul class="mt-auto space-y-2 text-sm text-gray-500">
                        @foreach ((array) __('landing.features.housing.items') as $item)
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- Social proof (placeholders — replace with real testimonials) --}}
        <section class="border-t border-gray-800 bg-gray-900/30">
            <div class="max-w-6xl mx-auto px-6 py-20">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-white mb-3">{{ __('landing.social_proof.title') }}</h2>
                    <p class="text-gray-400">{{ __('landing.social_proof.subtitle') }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @for ($i = 0; $i < 3; $i++)
                        <figure class="bg-gray-900 border border-dashed border-gray-700 rounded-2xl p-6 flex flex-col gap-4">
                            <svg class="w-8 h-8 text-gray-700" fill="currentColor" viewBox="0 0 24 24"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
                            <blockquote class="text-gray-400 text-sm leading-relaxed flex-grow">
                                {{ __('landing.social_proof.placeholder_quote') }}
                            </blockquote>
                            <figcaption class="text-xs text-gray-600">{{ __('landing.social_proof.placeholder_attribution') }}</figcaption>
                        </figure>
                    @endfor
                </div>
                <div class="text-center mt-10">
                    <a href="mailto:jesusant.guerrero@gmail.com?subject=Loger%20testimonial"
                       class="text-sm text-primary hover:text-primary-dark transition-colors">
                        {{ __('landing.social_proof.cta') }} →
                    </a>
                </div>
            </div>
        </section>

        {{-- Why Loger --}}
        <section class="border-t border-gray-800 bg-gray-900/50">
            <div class="max-w-6xl mx-auto px-6 py-20 text-center">
                <h2 class="text-3xl font-bold text-white mb-4">{{ __('landing.why.title') }}</h2>
                <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">{{ __('landing.why.body') }}</p>
            </div>
        </section>

        {{-- Final CTA --}}
        <section class="max-w-6xl mx-auto px-6 py-20 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">{{ __('landing.final_cta.title') }}</h2>
            <p class="text-gray-400 mb-8">{{ __('landing.final_cta.subtitle') }}</p>
            <a href="{{ route('register') }}"
               data-cta="footer-primary"
               class="inline-block bg-primary hover:bg-primary-dark text-white font-semibold px-10 py-4 rounded-lg transition-colors text-base shadow-lg shadow-primary/20">
                {{ __('landing.final_cta.cta') }}
            </a>
        </section>
    </main>

    @include('partials.marketing-footer')

</body>
</html>
