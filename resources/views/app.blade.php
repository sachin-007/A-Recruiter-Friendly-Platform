<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Assessment Platform') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite: CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>

    <!-- Global configuration -->
    <script>
        window.App = {
            name: '{{ config('app.name') }}',
            env: '{{ app()->environment() }}',
            url: '{{ config('app.url') }}',
            frontendUrl: '{{ config('app.frontend_url') ?? config('app.url') }}',
            csrfToken: '{{ csrf_token() }}',
        };
    </script>
</body>
</html>
