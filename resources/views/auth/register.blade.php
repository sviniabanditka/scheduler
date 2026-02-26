@extends('layouts.app')

@section('content')
    <div
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-100 via-purple-50 to-pink-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Створити акаунт
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Зареєструйтесь для управління розкладом вашого університету
                </p>
            </div>

            <!-- Form Card -->
            <div
                class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-gray-700/30 p-8">
                @if($errors->any())
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl">
                        <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Ваше ім'я
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                            placeholder="Іван Петренко">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Email
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                            placeholder="admin@university.edu.ua">
                    </div>

                    <div>
                        <label for="university_name"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Назва університету
                        </label>
                        <input id="university_name" type="text" name="university_name" value="{{ old('university_name') }}"
                            required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                            placeholder="Національний Університет">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Пароль
                        </label>
                        <input id="password" type="password" name="password" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                            placeholder="Мінімум 8 символів">
                    </div>

                    <div>
                        <label for="password_confirmation"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Підтвердження паролю
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                            placeholder="Повторіть пароль">
                    </div>

                    <button type="submit"
                        class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Зареєструватися
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                Вже маєте акаунт?
                <a href="{{ route('login') }}"
                    class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 transition-colors duration-200">
                    Увійти
                </a>
            </p>
        </div>
    </div>
@endsection