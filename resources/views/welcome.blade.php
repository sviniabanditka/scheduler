@extends('layouts.app')

@section('content')
    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <div
            class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
                <div class="text-center">
                    <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 tracking-tight">
                        Розклад <span class="bg-clip-text text-transparent bg-gradient-to-r from-yellow-200 to-pink-200">без
                            зусиль</span>
                    </h1>
                    <p class="text-xl md:text-2xl text-indigo-100 max-w-3xl mx-auto mb-10 leading-relaxed">
                        Автоматична генерація оптимального розкладу для вашого університету за лічені хвилини. Враховує
                        побажання викладачів та мінімізує вікна.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center px-8 py-4 bg-white text-indigo-700 font-bold rounded-xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-200 text-lg">
                            🚀 Почати безкоштовно
                        </a>
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center px-8 py-4 bg-white/10 text-white font-semibold rounded-xl backdrop-blur-sm border border-white/20 hover:bg-white/20 transition-all duration-200 text-lg">
                            Увійти
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Decorative wave -->
        <div class="absolute bottom-0 w-full">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M0 120L60 110C120 100 240 80 360 75C480 70 600 80 720 85C840 90 960 90 1080 80C1200 70 1320 50 1380 40L1440 30V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z"
                    class="fill-gray-50 dark:fill-gray-900" />
            </svg>
        </div>
    </div>

    <!-- Features Section -->
    <div class="bg-gray-50 dark:bg-gray-900 py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Все для ідеального розкладу
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                    Потужний алгоритм генерації з урахуванням десятків параметрів
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
                    <div
                        class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                        Автоматична генерація
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Розумний алгоритм автоматично створює оптимальний розклад за хвилини, враховуючи всі обмеження.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
                    <div
                        class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                        Побажання викладачів
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Враховує зайнятість та побажання кожного викладача щодо часу та днів проведення занять.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
                    <div
                        class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">
                        Публічне посилання
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                        Кожен університет отримує унікальне посилання для перегляду розкладу студентами без реєстрації.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- How it works -->
    <div class="bg-white dark:bg-gray-800 py-24 border-t border-gray-100 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Як це працює
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div
                        class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">1</span>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">Реєстрація</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Створіть акаунт та ваш університет</p>
                </div>

                <div class="text-center">
                    <div
                        class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">2</span>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">Дані</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Додайте групи, викладачів, аудиторії та предмети</p>
                </div>

                <div class="text-center">
                    <div
                        class="w-16 h-16 bg-pink-100 dark:bg-pink-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-pink-600 dark:text-pink-400">3</span>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">Генерація</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Один клік — і розклад готовий</p>
                </div>

                <div class="text-center">
                    <div
                        class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">4</span>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">Публікація</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Поділіться розкладом зі студентами</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-800 dark:to-purple-800 py-16">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                Готові спростити складання розкладу?
            </h2>
            <p class="text-xl text-indigo-100 mb-8">
                Зареєструйтесь та створіть ваш перший розклад прямо зараз
            </p>
            <a href="{{ route('register') }}"
                class="inline-flex items-center px-8 py-4 bg-white text-indigo-700 font-bold rounded-xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-200 text-lg">
                Зареєструватися безкоштовно →
            </a>
        </div>
    </div>
@endsection