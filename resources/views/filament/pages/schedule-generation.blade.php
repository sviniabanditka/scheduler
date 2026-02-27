<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div class="mt-6 flex gap-4">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-cpu-chip">
                Генерувати розклад
            </x-filament::button>

            <x-filament::button tag="a" :href="$this->tenantPublicUrl" target="_blank" color="gray"
                icon="heroicon-o-globe-alt">
                Публічна сторінка
            </x-filament::button>
        </div>
    </form>

    {{-- Recent versions table --}}
    <div class="mt-8" @if($this->hasGeneratingVersions) wire:poll.5s @endif>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Версії розкладу</h3>

        @if($this->recentVersions->count() > 0)
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Назва</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Календар</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Алгоритм</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Занять</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Порушень</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Створено</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дії</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->recentVersions as $version)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $version->name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $version->calendar?->name }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span @class([
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' => $version->status === 'draft',
                                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => $version->status === 'published',
                                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $version->status === 'archived',
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' => $version->status === 'generating',
                                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => $version->status === 'failed',
                                                ])>
                                                    @if($version->status === 'generating')
                                                        <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                    @endif
                                                    {{ match ($version->status) {
                                'draft' => 'Чернетка',
                                'published' => 'Опубліковано',
                                'archived' => 'Архів',
                                'generating' => 'Генерується...',
                                'failed' => 'Помилка',
                                default => $version->status,
                            } }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $params = $version->generation_params ?? [];
                                                    $algo = $params['algorithm'] ?? 'greedy';
                                                    $objVal = $params['objective_value'] ?? null;
                                                    $solveTime = $params['solve_time_ms'] ?? null;
                                                @endphp
                                                <span @class([
                                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' => $algo === 'greedy',
                                                    'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' => in_array($algo, ['annealing', 'cpsat']),
                                                    'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' => $algo === 'tabu',
                                                ])>
                                                    {{ match($algo) { 'annealing', 'cpsat' => 'Відпал', 'tabu' => 'Табу', default => 'Greedy' } }}
                                                </span>
                                                @if($objVal !== null && in_array($algo, ['annealing', 'cpsat', 'tabu']))
                                                    <span class="text-xs text-gray-400 ml-1" title="Objective value">obj:
                                                        {{ number_format($objVal, 1) }}</span>
                                                @endif
                                                @if($solveTime)
                                                    <span class="text-xs text-gray-400 ml-1">{{ number_format($solveTime / 1000, 1) }}с</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $version->assignments_count }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $version->violations_count }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $version->created_at->format('d.m.Y H:i') }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex gap-2">
                                                    @if($version->status === 'generating')
                                                        <span class="text-xs text-gray-400">
                                                            @if($version->generation_started_at)
                                                                {{ $version->generation_started_at->diffForHumans(null, true) }}
                                                            @endif
                                                        </span>
                                                    @else
                                                        @if($version->status === 'draft')
                                                            <x-filament::button size="xs" color="success"
                                                                wire:click="publishVersion({{ $version->id }})" icon="heroicon-o-check-circle">
                                                                Опублікувати
                                                            </x-filament::button>
                                                        @endif
                                                        @if(!in_array($version->status, ['archived', 'generating']))
                                                            <x-filament::button size="xs" color="gray"
                                                                wire:click="archiveVersion({{ $version->id }})" icon="heroicon-o-archive-box">
                                                                Архів
                                                            </x-filament::button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-3">📋</div>
                <p>Ще немає створених версій розкладу</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>