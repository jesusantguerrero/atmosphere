{{-- Shared head metadata for public marketing pages.
     $pageTitle, $pageDescription, $ogTitle, $ogDescription, $canonical can be set per-page. --}}
@php
    $title = $pageTitle ?? __('landing.meta.title');
    $description = $pageDescription ?? __('landing.meta.description');
    $ogTitleValue = $ogTitle ?? __('landing.meta.og_title');
    $ogDescriptionValue = $ogDescription ?? __('landing.meta.og_description');
    $canonicalUrl = $canonical ?? url()->current();
    $ogImage = url('/logo.png');
    $locale = app()->getLocale();
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">

<meta property="og:type" content="website">
<meta property="og:site_name" content="Loger">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $ogTitleValue }}">
<meta property="og:description" content="{{ $ogDescriptionValue }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="{{ $locale === 'es' ? 'es_DO' : 'en_US' }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitleValue }}">
<meta name="twitter:description" content="{{ $ogDescriptionValue }}">
<meta name="twitter:image" content="{{ $ogImage }}">

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

@include('partials.analytics')
