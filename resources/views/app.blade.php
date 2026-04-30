<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <!-- Primary Meta Tags -->
        <title>{{ config('app.name', 'Executive Dashboard') }}</title>
        <meta name="description" content="Dashboard Eksekutif SIMRS Khanza - Monitor kinerja rumah sakit secara real-time.">
        <meta name="keywords" content="SIMRS, Khanza, Dashboard, Hospital Management, Analytics">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('app.name', 'Executive Dashboard') }}">
        <meta property="og:description" content="Monitor kinerja rumah sakit secara real-time dengan dashboard eksekutif yang modern dan responsif.">
        <meta property="og:image" content="{{ asset('og-image.png') }}?v=1.0">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ url()->current() }}">
        <meta property="twitter:title" content="{{ config('app.name', 'Executive Dashboard') }}">
        <meta property="twitter:description" content="Monitor kinerja rumah sakit secara real-time dengan dashboard eksekutif yang modern dan responsif.">
        <meta property="twitter:image" content="{{ asset('og-image.png') }}?v=1.0">

        <link rel="icon" href="/favicon.png" type="image/png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head />
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
