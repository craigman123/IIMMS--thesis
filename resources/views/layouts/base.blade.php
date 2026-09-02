<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIIIMS | Authorized Access Only')</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/toast/toast.css') }}">
    <script src="{{ asset('js/login/login.js') }}"></script>
    <link rel="icon" href="{{ asset('images/icon.png') }}" type="image/x-icon">
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
    <div id="toast-container" class="toast-container"></div>
</body>
</html>