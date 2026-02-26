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
    <div class="mt-8">
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
                                                ])>
                                                    {{ match ($version->status) {
                                'draft' => 'Чернетка',
                                'published' => 'Опубліковано',
                                'archived' => 'Архів',
                                default => $version->status,
                            } }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $version->assignments_count }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $version->violations_count }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $version->created_at->format('d.m.Y H:i') }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <div class="flex gap-2">
                                                    @if($version->status === 'draft')
                                                        <x-filament::button size="xs" color="success"
                                                            wire:click="publishVersion({{ $version->id }})" icon="heroicon-o-check-circle">
                                                            Опублікувати
                                                        </x-filament::button>
                                                    @endif
                                                    @if($version->status !== 'archived')
                                                        <x-filament::button size="xs" color="gray"
                                                            wire:click="archiveVersion({{ $version->id }})" icon="heroicon-o-archive-box">
                                                            Архів
                                                        </x-filament::button>
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