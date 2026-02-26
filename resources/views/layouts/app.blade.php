<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ 
    mobileMenuOpen: false, 
    darkMode: localStorage.getItem('darkMode') === 'true',
    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
    init() {
        // Apply dark mode on page load
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <!-- Vite removed - using CDN instead -->

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <div class="flex-shrink-0">
                            <a href="{{ url('/') }}" class="text-xl font-bold text-gray-800 dark:text-white">
                                {{ config('app.name', 'Laravel') }}
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ url('/') }}"
                                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:text-gray-300">
                                Home
                            </a>
                            <a href="{{ url('/admin') }}"
                                class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:text-gray-300">
                                Admin
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Auth Links -->
                        @auth
                            <span class="hidden sm:inline text-sm text-gray-600 dark:text-gray-400">
                                {{ Auth::user()->name }}
                            </span>
                            <a href="{{ url('/admin') }}"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                Панель
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit"
                                    class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                                    Вийти
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}"
                                class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                Увійти
                            </a>
                            <a href="{{ route('register') }}"
                                class="text-sm font-medium px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all">
                                Реєстрація
                            </a>
                        @endauth

                        <!-- Dark Mode Toggle -->
                        <button @click="toggleDarkMode()"
                            class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out">
                            <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg x-show="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        <!-- Mobile menu button -->
                        <div class="sm:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div x-show="mobileMenuOpen" x-cloak class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <a href="{{ url('/') }}"
                        class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700">
                        Home
                    </a>
                    <a href="{{ url('/admin') }}"
                        class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700">
                        Admin
                    </a>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-gray-500 dark:text-gray-400">
                        &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>

</body>

</html>