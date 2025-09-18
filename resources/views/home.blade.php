@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-8">
                        🚀 Laravel Scheduler
                    </h1>
                    <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                        Ласкаво просимо до вашого Laravel проекту з Docker середовищем!
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg">
                            <div class="text-blue-600 dark:text-blue-400 text-3xl mb-4">🐳</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Docker Ready</h3>
                            <p class="text-gray-600 dark:text-gray-300">Повністю налаштоване Docker середовище з PHP 8.2, Nginx, MySQL та Redis</p>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg">
                            <div class="text-green-600 dark:text-green-400 text-3xl mb-4">⚡</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Filament Admin</h3>
                            <p class="text-gray-600 dark:text-gray-300">Сучасна адмін панель на базі Laravel Filament v3</p>
                        </div>
                        
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-lg">
                            <div class="text-purple-600 dark:text-purple-400 text-3xl mb-4">🎨</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Tailwind + Alpine</h3>
                            <p class="text-gray-600 dark:text-gray-300">Красивий UI з Tailwind CSS та інтерактивність на Alpine.js</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex justify-center space-x-4">
                            <a href="{{ url('/admin') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-150 ease-in-out">
                                Відкрити адмінку
                            </a>
                            <button @click="showInfo = !showInfo" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition duration-150 ease-in-out">
                                Показати інформацію
                            </button>
                        </div>
                        
                        <div x-show="showInfo" x-cloak class="mt-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Інформація про проект</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <strong class="text-gray-900 dark:text-white">Laravel:</strong> {{ app()->version() }}
                                </div>
                                <div>
                                    <strong class="text-gray-900 dark:text-white">PHP:</strong> {{ PHP_VERSION }}
                                </div>
                                <div>
                                    <strong class="text-gray-900 dark:text-white">Environment:</strong> {{ app()->environment() }}
                                </div>
                                <div>
                                    <strong class="text-gray-900 dark:text-white">Debug:</strong> {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('home', () => ({
            showInfo: false
        }));
    });
</script>
@endsection
