@if ($id = config('services.google_analytics.measurement_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($id));
    </script>
@endif
