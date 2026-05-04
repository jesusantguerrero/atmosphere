@extends('legal.layout', [
    'legalTitle' => __('landing.footer.privacy'),
    'legalUpdated' => 'Last updated: 2026-05-03',
    'legalDescription' => 'Privacy policy for Loger — what we collect, how we use it, and your rights.',
])

@section('content')
    <p class="text-gray-300 leading-relaxed">
        Loger is a personal finance app built by an independent developer. This page explains
        what data we collect and how we use it. We aim to collect as little as possible.
    </p>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Data we collect</h2>
    <ul class="text-gray-300 leading-relaxed space-y-2 list-disc list-inside">
        <li>Account information you provide: name, email, and a password hash.</li>
        <li>Financial data you enter: budgets, transactions, accounts, payees, recipes, and notes.</li>
        <li>PDF statements you upload — parsed locally and stored on our servers.</li>
        <li>Anonymous usage analytics (Google Analytics) to help us improve the product.</li>
        <li>Push notification tokens (OneSignal), only if you opt in.</li>
    </ul>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">How we use it</h2>
    <p class="text-gray-300 leading-relaxed">
        Your financial data is used solely to provide you the service. We do not sell your data,
        share it with advertisers, or use it to train machine learning models.
    </p>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Your rights</h2>
    <p class="text-gray-300 leading-relaxed">
        You can export or delete your data at any time from your account settings. To request
        a full account deletion, email
        <a href="mailto:jesusant.guerrero@gmail.com">jesusant.guerrero@gmail.com</a>.
    </p>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Third-party services</h2>
    <ul class="text-gray-300 leading-relaxed space-y-2 list-disc list-inside">
        <li>Google Analytics — anonymous traffic measurement.</li>
        <li>OneSignal — opt-in web push notifications.</li>
        <li>Google Sign-In / Drive (optional) — only if you connect them.</li>
    </ul>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Contact</h2>
    <p class="text-gray-300 leading-relaxed">
        Questions? Email <a href="mailto:jesusant.guerrero@gmail.com">jesusant.guerrero@gmail.com</a>.
    </p>
@endsection
