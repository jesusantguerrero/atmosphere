<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.marketing-head', [
        'pageTitle' => __('landing.errors.404_title') . ' — Loger',
        'pageDescription' => __('landing.errors.404_body'),
    ])
</head>
<body class="bg-gray-950 text-gray-100 antialiased">

    @include('partials.marketing-nav')

    <main class="max-w-2xl mx-auto px-6 py-32 text-center">
        <p class="text-primary font-mono text-sm tracking-wider mb-4">404</p>
        <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">{{ __('landing.errors.404_title') }}</h1>
        <p class="text-gray-400 mb-10">{{ __('landing.errors.404_body') }}</p>
        <a href="{{ route('landing') }}"
           class="inline-block bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3 rounded-lg transition-colors">
            {{ __('landing.errors.404_cta') }}
        </a>
    </main>

    @include('partials.marketing-footer')

</body>
</html>
