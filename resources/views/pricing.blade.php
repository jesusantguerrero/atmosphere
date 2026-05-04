<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.marketing-head', [
        'pageTitle' => __('landing.pricing.meta_title'),
        'pageDescription' => __('landing.pricing.meta_description'),
        'ogTitle' => __('landing.pricing.meta_title'),
        'ogDescription' => __('landing.pricing.meta_description'),
    ])
</head>
<body class="bg-gray-950 text-gray-100 antialiased">

    @include('partials.marketing-nav')

    <main>
        <section class="max-w-6xl mx-auto px-6 pt-20 pb-12 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4">{{ __('landing.pricing.title') }}</h1>
            <p class="text-lg text-gray-400 max-w-2xl mx-auto">{{ __('landing.pricing.subtitle') }}</p>
        </section>

        <section class="max-w-5xl mx-auto px-6 pb-20">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Free --}}
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 flex flex-col gap-6">
                    <div>
                        <div class="flex items-baseline justify-between mb-1">
                            <h2 class="text-2xl font-bold text-white">{{ __('landing.pricing.free.name') }}</h2>
                        </div>
                        <p class="text-gray-400 text-sm">{{ __('landing.pricing.free.tagline') }}</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-bold text-white">{{ __('landing.pricing.free.price') }}</span>
                        <span class="text-gray-500 text-sm">{{ __('landing.pricing.free.cadence') }}</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-300 flex-grow">
                        @foreach ((array) __('landing.pricing.free.features') as $feature)
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}"
                       data-cta="pricing-free"
                       class="block text-center bg-gray-800 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                        {{ __('landing.pricing.free.cta') }}
                    </a>
                </div>

                {{-- Plus --}}
                <div class="bg-gradient-to-b from-primary/10 to-gray-900 border border-primary/40 rounded-2xl p-8 flex flex-col gap-6 relative">
                    <div class="absolute -top-3 right-6 bg-primary text-white text-xs font-semibold px-3 py-1 rounded-full">
                        {{ __('landing.pricing.plus.badge') }}
                    </div>
                    <div>
                        <div class="flex items-baseline justify-between mb-1">
                            <h2 class="text-2xl font-bold text-white">{{ __('landing.pricing.plus.name') }}</h2>
                        </div>
                        <p class="text-gray-400 text-sm">{{ __('landing.pricing.plus.tagline') }}</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-bold text-white">{{ __('landing.pricing.plus.price') }}</span>
                        <span class="text-gray-500 text-sm">{{ __('landing.pricing.plus.cadence') }}</span>
                    </div>
                    <ul class="space-y-3 text-sm text-gray-300 flex-grow">
                        @foreach ((array) __('landing.pricing.plus.features') as $feature)
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}"
                       data-cta="pricing-plus"
                       class="block text-center bg-primary hover:bg-primary-dark text-white font-semibold px-6 py-3 rounded-lg transition-colors shadow-lg shadow-primary/20">
                        {{ __('landing.pricing.plus.cta') }}
                    </a>
                </div>
            </div>

            <p class="text-center text-sm text-gray-500 mt-10 max-w-2xl mx-auto">
                {{ __('landing.pricing.note') }}
            </p>
        </section>
    </main>

    @include('partials.marketing-footer')

</body>
</html>
