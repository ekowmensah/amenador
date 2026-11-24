<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="same-origin">
    <meta name="theme-color" content="{{ $pagePreset['theme'] }}">
    <title>@yield('title', 'Shop') · {{ $store->name }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ route('storefront.asset', 'store.css') }}?v=1.0.0">
    @stack('styles')
    <link rel="stylesheet" href="{{ $storefrontPresetCss }}">
    <link rel="stylesheet" href="{{ route('storefront.asset', 'appearance.css') }}?v={{ config('storefront.module_version') }}">
</head>
<body class="pcp-page sf-themed public-layout-{{ $pageLayout }}" data-public-layout="{{ $pageLayout }}">
@yield('content')
@stack('scripts')
</body>
</html>
