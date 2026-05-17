<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Somewhere')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="gradient-bg text-white py-4 shadow-lg">
        <div class="container mx-auto px-4">
            <h1 class="text-2xl font-bold">Somewhere</h1>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; {{ date('Y') }} Somewhere. Tous droits réservés.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
