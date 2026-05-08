<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Loger') }}</title>
        <link rel="manifest" href="/manifest.json" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="icon" href="/favicon.ico">
        {{-- @laravelPWA --}}
        <meta name="description" content="Loger — personal finance and home management for the rest of the world.">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
        <link rel="mask-icon" href="/mask-icon.svg" color="#FFFFFF">
        <meta name="theme-color" content="#ffffff">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Loger">
        <meta property="og:title" content="Loger">
        <meta property="og:description" content="Personal finance and home management for the rest of the world.">
        <meta property="og:image" content="{{ url('/logo.png') }}">
        <meta name="twitter:card" content="summary_large_image">

        @include('partials.analytics')

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog==" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
        <!-- Scripts -->
        <script src="https://apis.google.com/js/api.js" defer></script>
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        @if (config('services.onesignal.app_id'))
        <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
        <script>
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function(OneSignal) {
            await OneSignal.init({
                appId: @json(config('services.onesignal.app_id')),
                safari_web_id: "web.onesignal.auto.3f550615-46c0-4fa5-9ee8-42953ece3d19",
                notifyButton: { enable: true },
            });
            // Link this subscription to a Loger user_id so the server can
            // target push notifications at a specific person (credit-card
            // nudges, overdue relationships, etc.) via `include_aliases`.
            @auth
            try { await OneSignal.login(@json((string) auth()->id())); } catch (e) { /* best-effort */ }
            @endauth
        });
        </script>
        @endif
    </head>
    <body class="font-sans antialiased">
        @inertia
        <script>
        </script>
    </body>
</html>
