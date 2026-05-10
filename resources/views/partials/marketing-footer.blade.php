<footer class="border-t border-gray-800 bg-gray-950">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-8 mb-8">
            <div>
                <img src="/logo.svg" alt="Loger" class="h-6 w-auto brightness-0 invert opacity-70 mb-3">
                <p class="text-sm text-gray-500 leading-relaxed">{{ __('landing.footer.tagline') }}</p>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">{{ __('landing.footer.product') }}</h3>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('pricing') }}" class="hover:text-gray-300 transition-colors">{{ __('landing.footer.pricing') }}</a></li>
                    <li><a href="https://loger.neatlancer.com" target="_blank" rel="noopener noreferrer" class="hover:text-gray-300 transition-colors">{{ __('landing.footer.demo') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">{{ __('landing.footer.open_source') }}</h3>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li>
                        <a href="https://github.com/jesusantguerrero/atmosphere"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 hover:text-gray-300 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.07 3.29 9.37 7.86 10.89.58.11.79-.25.79-.56v-2.16c-3.2.69-3.88-1.37-3.88-1.37-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.75 2.68 1.24 3.34.95.1-.74.4-1.24.72-1.53-2.55-.29-5.24-1.28-5.24-5.71 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.18a10.93 10.93 0 0 1 5.74 0c2.19-1.49 3.15-1.18 3.15-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.44-2.7 5.41-5.27 5.7.41.36.78 1.06.78 2.13v3.16c0 .31.21.67.8.56 4.56-1.52 7.85-5.82 7.85-10.89C23.5 5.65 18.35.5 12 .5z"/></svg>
                            {{ __('landing.footer.atmosphere') }}
                        </a>
                    </li>
                    <li><span class="text-gray-600">{{ __('landing.footer.self_host') }}</span></li>
                    <li><a href="{{ route('open-source') }}" class="hover:text-gray-300 transition-colors">{{ __('landing.footer.open_source_page') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">{{ __('landing.footer.legal') }}</h3>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('privacy-policy') }}" class="hover:text-gray-300 transition-colors">{{ __('landing.footer.privacy') }}</a></li>
                    <li><a href="{{ route('terms-of-service') }}" class="hover:text-gray-300 transition-colors">{{ __('landing.footer.terms') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-900 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600">
            <p>{{ __('landing.footer.powered_by') }}</p>
            <p>{{ __('landing.footer.built_by') }}</p>
        </div>
    </div>
</footer>
