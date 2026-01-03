<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#3b82f6" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<!-- PWA Meta Tags -->
<link rel="manifest" href="/manifest.json">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="المخادع">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

<!-- Runtime Reverb Configuration (must be before Vite) -->
<script>
    window.__reverbConfig = {
        key: '{{ config('broadcasting.connections.reverb.key') }}',
        host: '{{ config('broadcasting.connections.reverb.options.host') }}',
        port: {{ config('broadcasting.connections.reverb.options.port') }},
        scheme: '{{ config('broadcasting.connections.reverb.options.scheme') }}',
    };
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
