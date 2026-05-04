<footer class="border-t border-gray-800 bg-gray-950">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 mb-8">
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
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">{{ __('landing.footer.legal') }}</h3>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="{{ route('privacy-policy') }}" class="hover:text-gray-300 transition-colors">{{ __('landing.footer.privacy') }}</a></li>
                    <li><a href="{{ route('terms-of-service') }}" class="hover:text-gray-300 transition-colors">{{ __('landing.footer.terms') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-900 pt-6 text-center text-xs text-gray-600">
            {{ __('landing.footer.built_by') }}
        </div>
    </div>
</footer>
