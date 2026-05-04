@extends('legal.layout', [
    'legalTitle' => __('landing.footer.terms'),
    'legalUpdated' => 'Last updated: 2026-05-03',
    'legalDescription' => 'Terms of service for Loger — what you can expect from us, and what we expect from you.',
])

@section('content')
    <p class="text-gray-300 leading-relaxed">
        By creating an account on Loger you agree to these terms. They are intentionally short.
    </p>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">The service</h2>
    <p class="text-gray-300 leading-relaxed">
        Loger is provided as-is for personal finance and household management. The Free plan
        is offered at no cost. The Plus plan is paid and currently available in early access
        for users in the Dominican Republic.
    </p>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Your account</h2>
    <p class="text-gray-300 leading-relaxed">
        You are responsible for keeping your account credentials secure. Don\'t share them.
        You may close your account at any time from your account settings.
    </p>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Acceptable use</h2>
    <ul class="text-gray-300 leading-relaxed space-y-2 list-disc list-inside">
        <li>No automated scraping of other users\' data.</li>
        <li>No attempts to break, exploit, or overload the service.</li>
        <li>No use of Loger for illegal activity.</li>
    </ul>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Disclaimer</h2>
    <p class="text-gray-300 leading-relaxed">
        Loger is a tool — not a financial advisor. We do not guarantee correctness of
        calculations, exchange rates, or imported statement data. Always verify with your
        bank before making important decisions.
    </p>

    <h2 class="text-xl font-semibold text-white mt-10 mb-3">Changes</h2>
    <p class="text-gray-300 leading-relaxed">
        We may update these terms over time. Material changes will be communicated by email
        or in-app notice.
    </p>
@endsection
