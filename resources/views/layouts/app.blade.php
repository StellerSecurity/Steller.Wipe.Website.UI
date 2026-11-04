<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Stellar Protect</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet">
    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('build/assets/style.css') }}?v={{ filemtime(public_path('build/assets/style.css')) }}">
    @stack('scripts')
</head>
<body>
    <header class="stellar-header">
        @include('layouts.header')
    </header>
    <main class="wipe-main">
        @yield('content')
    </main>
</body>
</html>
