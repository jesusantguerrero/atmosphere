<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loger — Digital Home Management</title>
    <meta name="description" content="Loger is a personal finance and home management tool built for people outside the traditional US banking system. Track budgets, transactions, meals, and more.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&family=Pacifico&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        brand: ['Pacifico', 'cursive'],
                        sans: ['Nunito', 'sans-serif'],
                    },
                    colors: {
                        primary: '#F37EA1',
                        'primary-dark': '#d4617f',
                        'primary-light': '#fce4ec',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito', sans-serif; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 antialiased">

    {{-- Navigation --}}
    <header class="border-b border-gray-800 bg-gray-950/80 backdrop-blur-sm sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <img src="/logo.svg" alt="Loger" class="h-8 w-auto brightness-0 invert">
            </a>
            <nav class="flex items-center gap-6">
                <a href="https://loger.neatlancer.com"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="text-sm text-gray-400 hover:text-gray-100 transition-colors">
                    Demo
                </a>
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-gray-100 hover:text-primary transition-colors">
                    Login
                </a>
            </nav>
        </div>
    </header>

    <main>
        {{-- Hero Section --}}
        <section class="max-w-6xl mx-auto px-6 pt-24 pb-20 text-center">
            <div class="inline-flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-full px-4 py-1.5 text-sm text-gray-400 mb-8">
                <span class="w-2 h-2 rounded-full bg-primary inline-block"></span>
                Personal finance, reimagined
            </div>

            <h1 class="text-5xl sm:text-6xl font-bold leading-tight tracking-tight mb-6">
                The Digital Home<br>
                <span class="text-primary">Management Software</span>
            </h1>

            <p class="text-lg sm:text-xl text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                Loger gives you full control over your finances without depending on US-centric banking integrations.
                Built for real-world budgeting — multi-currency, flexible, and entirely yours.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto inline-block bg-primary hover:bg-primary-dark text-white font-semibold px-8 py-3.5 rounded-lg transition-colors text-base">
                    Get Started Free
                </a>
                <a href="https://loger.neatlancer.com"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="w-full sm:w-auto inline-block border border-gray-700 hover:border-gray-500 text-gray-300 hover:text-white font-medium px-8 py-3.5 rounded-lg transition-colors text-base">
                    Try the Demo
                </a>
            </div>
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
                        <h2 class="text-lg font-semibold text-white mb-2">Personal Finance</h2>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Full budget management with transaction tracking, account reconciliation, and multi-currency support. Works with any bank, anywhere in the world.
                        </p>
                    </div>
                    <ul class="mt-auto space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Budgets and spending categories
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Multi-currency account balances
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Bank statement reconciliation
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            PDF statement import
                        </li>
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
                        <h2 class="text-lg font-semibold text-white mb-2">Meal Planning</h2>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Plan weekly meals, manage recipes, and track ingredients. Connect food spending directly to your household budget for a complete picture.
                        </p>
                    </div>
                    <ul class="mt-auto space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Recipe and ingredient library
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Weekly meal calendar
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Grocery cost tracking
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Food budget integration
                        </li>
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
                        <h2 class="text-lg font-semibold text-white mb-2">Housing Management</h2>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Keep your home organized. Track recurring bills, services, and household tasks all in one place alongside your finances.
                        </p>
                    </div>
                    <ul class="mt-auto space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Recurring bill tracking
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Home service records
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Household task management
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                            Utility expense history
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- Why Loger --}}
        <section class="border-t border-gray-800 bg-gray-900/50">
            <div class="max-w-6xl mx-auto px-6 py-20 text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Built for the rest of the world</h2>
                <p class="text-gray-400 max-w-xl mx-auto leading-relaxed">
                    Most personal finance apps assume you have a US bank account with automatic import. Loger is different —
                    it works with any financial institution, supports multiple currencies natively, and puts you in control of your own data.
                </p>
            </div>
        </section>

        {{-- Final CTA --}}
        <section class="max-w-6xl mx-auto px-6 py-20 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Ready to take control?</h2>
            <p class="text-gray-400 mb-8">Create a free account and start managing your finances today.</p>
            <a href="{{ route('register') }}"
               class="inline-block bg-primary hover:bg-primary-dark text-white font-semibold px-10 py-4 rounded-lg transition-colors text-base">
                Create Free Account
            </a>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-800 bg-gray-950">
        <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500">
            <div class="flex items-center gap-3">
                <img src="/logo.svg" alt="Loger" class="h-5 w-auto brightness-0 invert opacity-50">
            </div>
            <p>Built by Jesus Guerrero &mdash; Released under the BSD-3-Clause License</p>
        </div>
    </footer>

</body>
</html>
