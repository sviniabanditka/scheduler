<x-filament-panels::page>
    <div class="text-center">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Розклад занять
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                Переглядайте актуальний розклад занять для всіх груп
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 max-w-2xl mx-auto">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-blue-600 dark:text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Перейти до розкладу
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Натисніть кнопку нижче, щоб перейти на головну сторінку з розкладом занять
                </p>
            </div>
            
            <a href="/" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-colors duration-200">
                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Перейти до розкладу
            </a>
        </div>
        
        <div class="mt-8 text-sm text-gray-500 dark:text-gray-400">
            <p>Ви можете також перейти до розкладу, набравши адресу <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">/</code> у браузері</p>
        </div>
    </div>
</x-filament-panels::page>
