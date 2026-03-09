<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gallery') - The Yelling Light</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-black text-white font-sans antialiased min-h-screen">
    <header class="border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
            <span class="text-lg font-bold tracking-wider text-gray-300">THE YELLING LIGHT</span>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-gray-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
            <p class="text-xs text-gray-600 text-center">&copy; {{ date('Y') }} The Yelling Light</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
