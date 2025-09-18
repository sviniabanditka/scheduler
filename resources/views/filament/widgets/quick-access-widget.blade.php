<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-rocket-launch class="h-5 w-5" />
                Швидкий доступ
            </div>
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Главная страница расписания -->
            <a href="/" 
               target="_blank"
               class="group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 hover:shadow-lg transition-all duration-200 hover:border-blue-300 dark:hover:border-blue-600">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                            <x-heroicon-o-calendar-days class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                            Головна сторінка розкладу
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Переглянути розклад для всіх груп
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4 text-gray-400 group-hover:text-blue-500" />
                    </div>
                </div>
            </a>
            
            <!-- Управление расписанием -->
            <a href="{{ route('filament.admin.pages.schedule-management') }}" 
               class="group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 hover:shadow-lg transition-all duration-200 hover:border-green-300 dark:hover:border-green-600">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                            <x-heroicon-o-cog-6-tooth class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">
                            Управління розкладом
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Редагувати та додавати заняття
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <x-heroicon-o-arrow-right class="h-4 w-4 text-gray-400 group-hover:text-green-500" />
                    </div>
                </div>
            </a>
            
            <!-- Статистика -->
            <a href="{{ route('filament.admin.pages.dashboard') }}" 
               class="group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 hover:shadow-lg transition-all duration-200 hover:border-purple-300 dark:hover:border-purple-600">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                            <x-heroicon-o-chart-bar class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">
                            Статистика
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Переглянути метрики та аналітику
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <x-heroicon-o-arrow-right class="h-4 w-4 text-gray-400 group-hover:text-purple-500" />
                    </div>
                </div>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
