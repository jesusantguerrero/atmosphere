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
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.07 3.29 9.37 7.86 10.89.58.11.79-.25.79-.56v-2.16c-3.2.69-3.88-1.37-3.88-