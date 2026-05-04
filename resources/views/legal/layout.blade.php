<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.marketing-head', [
        'pageTitle' => ($legalTitle ?? '') . ' — Loger',
        'pageDescription' => $legalDescription ?? __('landing.meta.description'),
    ])
</head>
<body class="bg-gray-950 text-gray-100 antialiased">

    @include('partials.marketing-nav')

    <main class="max-w-3xl mx-auto px-6 py-16">
        <article class="prose prose-invert prose-headings:text-white prose-p:text-gray-300 prose-a:text-primary prose-strong:text-white max-w-none">
            <h1 class="text-3xl font-bold text-white mb-2">{{ $legalTitle ?? '' }}</h1>
            <p class="text-sm text-gray-500 mb-10">{{ $legalUpdated ?? '' }}</p>

            {{ $slot ?? '' }}
            @yield('content')
        </article>
    </main>

    @include('partials.marketing-footer')

</body>
</html>
