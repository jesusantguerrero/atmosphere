@php
    $currentLocale = app()->getLocale();
    $altLocale = $currentLocale === 'es' ? 'en' : 'es';
    $altLabel = $currentLocale === 'es' ? 'EN' : 'ES';
    $localeSwitchUrl = request()->fullUrlWithQuery(['lang' => $altLocale]);
@endphp
<header class="border-b border-gray-800 bg-gray-950/80 backdrop-blur-sm sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="{{ route('landing') }}" class="flex items-center gap-3">
            <img src="/logo.svg" alt="Loger" class="h-8 w-auto brightness-0 invert">
        </a>
        <nav class="flex items-center gap-6">
            <a href="{{ route('pricing') }}"
               class="text-sm text-gray-400 hover:text-gray-100 transition-colors hidden sm:inline">
                {{ __('landing.nav.pricing') }}
            </a>
            <a href="https://loger.neatlancer.com"
               target="_blank"
               rel="noopener noreferrer"
               class="text-sm text-gray-400 hover:text-gray-100 transition-colors hidden sm:inline">
                {{ __('landing.nav.demo') }}
            </a>
            <a href="{{ $localeSwitchUrl }}"
               class="text-xs font-medium text-gray-500 hover:text-gray-300 transition-colors border border-gray-800 rounded-md px-2 py-1"
               aria-label="Switch language">
                {{ $altLabel }}
            </a>
            <a href="{{ route('login') }}"
               class="hidden sm:inline-block text-sm font-medium text-gray-100 hover:text-primary transition-colors">
                {{ __('landing.nav.login') }}
            </a>
            <a href="{{ route('register') }}"
               class="text-sm font-semibold bg-primary hover:bg-primary-dark text-white px-3 sm:px-4 py-2 rounded-lg transition-colors">
                {{ __('landing.nav.sign_up') }}
            </a>
        </nav>
    </div>
</header>
