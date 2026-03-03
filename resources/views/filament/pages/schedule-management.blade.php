<x-filament-panels::page>
    <style>
        .schedule-cell { min-width: 160px; min-height: 60px; }
        .schedule-item { transition: all 0.2s; }
        .schedule-item:hover { transform: scale(1.02); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .type-lecture { background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-left: 4px solid #3b82f6; color: #1e40af; }
        .type-practice { background: linear-gradient(135deg, #dcfce7, #bbf7d0); border-left: 4px solid #22c55e; color: #166534; }
        .type-lab { background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b; color: #92400e; }
        .type-seminar { background: linear-gradient(135deg, #fee2e2, #fecaca); border-left: 4px solid #ef4444; color: #991b1b; }
        .type-pc { background: linear-gradient(135deg, #f3e8ff, #e9d5ff); border-left: 4px solid #a855f7; color: #6b21a8; }
        .type-default { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); border-left: 4px solid #6b7280; color: #374151; }
        .dark .type-lecture { background: linear-gradient(135deg, #1e3a8a, #1e40af); color: #dbeafe; }
        .dark .type-practice { background: linear-gradient(135deg, #14532d, #166534); color: #dcfce7; }
        .dark .type-lab { background: linear-gradient(135deg, #78350f, #92400e); color: #fef3c7; }
        .dark .type-seminar { background: linear-gradient(135deg, #7f1d1d, #991b1b); color: #fee2e2; }
        .dark .type-pc { background: linear-gradient(135deg, #581c87, #6b21a8); color: #f3e8ff; }
        .dark .type-default { background: linear-gradient(135deg, #374151, #4b5563); color: #e5e7eb; }
    </style>

    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Version --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Версія розкладу</label>
                    <select wire:model.live="selectedVersion"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Оберіть версію</option>
                        @foreach($this->versions as $version)
                            <option value="{{ $version->id }}">
                                {{ $version->name }}
                                ({{ match($version->status) { 'draft' => '⬜ Чернетка', 'published' => '🟢 Опубліковано', 'archived' => '⬛ Архів', default => $version->status } }})
                                — {{ $version->assignments_count }} занять
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Group --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Група (фільтр)</label>
                    <select wire:model.live="selectedGroup"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Всі групи</option>
                        @foreach($this->groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Start Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Від</label>
                    <input type="date" wire:model.live="startDate"
                        @if($this->calendar) min="{{ $this->calendar->start_date->format('Y-m-d') }}" max="{{ $this->calendar->end_date->format('Y-m-d') }}" @endif
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500">
                </div>

                {{-- End Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">До</label>
                    <input type="date" wire:model.live="endDate"
                        @if($this->calendar) min="{{ $this->calendar->start_date->format('Y-m-d') }}" max="{{ $this->calendar->end_date->format('Y-m-d') }}" @endif
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            @if($this->calendar)
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    📅 Календар: {{ $this->calendar->name }} ({{ $this->calendar->start_date->format('d.m.Y') }} — {{ $this->calendar->end_date->format('d.m.Y') }})
                </div>
            @endif
        </div>

        {{-- Stats Toggle + Panel --}}
        @if($this->selectedVersion)
            <div class="flex gap-2">
                <button wire:click="toggleStats"
                    class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium transition
                        {{ $showStats
                            ? 'bg-primary-50 border-primary-300 text-primary-700 dark:bg-primary-900/30 dark:border-primary-600 dark:text-primary-300'
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    {{ $showStats ? 'Сховати статистику' : 'Статистика предметів' }}
                </button>
            </div>

            @if($showStats)
                @php
                    $stats = $this->subjectStats;
                    $totalMissing = collect($stats)->where('status', 'missing')->count();
                    $totalExcess = collect($stats)->where('status', 'excess')->count();
                    $totalOk = collect($stats)->where('status', 'ok')->count();
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Статистика покриття предметів</h2>
                        <div class="flex gap-3 text-sm">
                            <span class="text-green-600 dark:text-green-400 font-medium">{{ $totalOk }} ок</span>
                            <span class="text-red-600 dark:text-red-400 font-medium">{{ $totalMissing }} недостатньо</span>
                            <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ $totalExcess }} надлишок</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Предмет</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Групи</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Викладачі</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Потрібно</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Призначено</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Різниця</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($stats as $row)
                                    @if($row['status'] !== 'ok')
                                    <tr @class([
                                        'bg-red-50 dark:bg-red-900/10' => $row['status'] === 'missing',
                                        'bg-yellow-50 dark:bg-yellow-900/10' => $row['status'] === 'excess',
                                        'cursor-pointer hover:bg-red-100 dark:hover:bg-red-900/20' => $row['status'] === 'missing',
                                        'cursor-pointer hover:bg-yellow-100 dark:hover:bg-yellow-900/20' => $row['status'] === 'excess',
                                    ])
                                        @if($row['status'] === 'missing')
                                            wire:click="openStatsAddModal({{ $row['activity_id'] }})"
                                        @elseif($row['status'] === 'excess')
                                            wire:click="openStatsDeleteModal({{ $row['activity_id'] }})"
                                        @endif
                                    >
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">{{ $row['subject'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $row['type'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $row['groups'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $row['teachers'] }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ $row['required'] }}</td>
                                        <td class="px-4 py-2 text-sm text-center">{{ $row['assigned'] }}</td>
                                        <td class="px-4 py-2 text-sm text-center font-medium">
                                            <span @class([
                                                'text-red-600 dark:text-red-400' => $row['status'] === 'missing',
                                                'text-yellow-600 dark:text-yellow-400' => $row['status'] === 'excess',
                                            ])>
                                                {{ $row['diff'] >= 0 ? '+' : '' }}{{ $row['diff'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        {{-- Schedule Table --}}
        @php $data = $this->scheduleData; @endphp

        @if($this->selectedVersion && !empty($data['dateRange']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Розклад
                        @if($this->selectedGroup)
                            — {{ $this->groups->firstWhere('id', $this->selectedGroup)?->name }}
                        @endif
                    </h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ count($data['dateRange']) }} днів
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase border-b dark:border-gray-600 w-20 sticky left-0 bg-gray-50 dark:bg-gray-700/50 z-10">
                                    Пара
                                </th>
                                @foreach($data['dateRange'] as $day)
                                    <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase border-b dark:border-gray-600 schedule-cell">
                                        <div>{{ $day['day_name'] }}</div>
                                        <div class="text-xs font-normal">{{ $day['formatted'] }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->timeSlots as $slot)
                                <tr>
                                    <td class="px-3 py-2 text-xs font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800 z-10">
                                        <div class="text-gray-400">{{ $slot->slot_index }} пара</div>
                                        <div>{{ $slot->start_time->format('H:i') }}-{{ $slot->end_time->format('H:i') }}</div>
                                    </td>
                                    @foreach($data['dateRange'] as $day)
                                        @php $items = $data['matrix'][$day['date']][$slot->slot_index] ?? []; @endphp
                                        <td class="px-1 py-1 border-b dark:border-gray-700 schedule-cell align-top">
                                            @if(count($items) > 0)
                                                <div class="space-y-1">
                                                    @foreach($items as $item)
                                                        <div class="schedule-item rounded-lg p-2 text-xs cursor-pointer relative group
                                                            type-{{ $item['type'] ?? 'default' }}"
                                                            wire:click="openEditModal({{ $item['id'] }})">
                                                            <div class="font-semibold truncate">{{ $item['subject'] }}</div>
                                                            <div class="truncate opacity-80">{{ $item['teacher'] }}</div>
                                                            @if($item['groups'])
                                                                <div class="truncate opacity-70 text-[10px]">{{ $item['groups'] }}</div>
                                                            @endif
                                                            @if($item['room'])
                                                                <div class="truncate opacity-70">🏫 {{ $item['room'] }}</div>
                                                            @endif
                                                            @if($item['parity'] !== 'both')
                                                                <span class="absolute top-1 right-1 text-[9px] px-1 rounded bg-white/50 dark:bg-black/30">
                                                                    {{ $item['parity'] === 'num' ? 'Ч' : 'З' }}
                                                                </span>
                                                            @endif
                                                            @if($item['locked'])
                                                                <span class="absolute bottom-1 right-1 text-[10px]">🔒</span>
                                                            @endif
                                                            {{-- Hover actions --}}
                                                            <div class="absolute top-0 right-0 hidden group-hover:flex gap-0.5 p-0.5">
                                                                <button wire:click.stop="toggleLock({{ $item['id'] }})"
                                                                    class="p-0.5 rounded bg-white/80 dark:bg-gray-800/80 text-xs hover:bg-white dark:hover:bg-gray-700"
                                                                    title="{{ $item['locked'] ? 'Розблокувати' : 'Заблокувати' }}">
                                                                    {{ $item['locked'] ? '🔓' : '🔒' }}
                                                                </button>
                                                                @if(!$item['locked'])
                                                                    <button wire:click.stop="deleteAssignment({{ $item['id'] }})"
                                                                        wire:confirm="Видалити це заняття?"
                                                                        class="p-0.5 rounded bg-white/80 dark:bg-gray-800/80 text-xs hover:bg-red-100 dark:hover:bg-red-900/50"
                                                                        title="Видалити">
                                                                        🗑️
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 p-2 text-center text-gray-400 dark:text-gray-500 text-xs h-12 flex items-center justify-center cursor-pointer hover:border-primary-400 hover:text-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition group"
                                                    wire:click="openCreateModal({{ $day['day_of_week'] }}, {{ $slot->slot_index }})">
                                                    <span class="group-hover:hidden">—</span>
                                                    <span class="hidden group-hover:inline">+ Додати</span>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif(!$this->selectedVersion)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-500 dark:text-gray-400">
                <div class="text-5xl mb-4">📋</div>
                <p class="text-lg">Оберіть версію розкладу для перегляду</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-500 dark:text-gray-400">
                <div class="text-5xl mb-4">📅</div>
                <p class="text-lg">Немає даних для обраного періоду</p>
            </div>
        @endif
    </div>

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.focus()" @keydown.escape="$wire.closeEditModal()">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeEditModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 z-10">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Редагувати заняття</h3>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Activity (subject) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Заняття (предмет)</label>
                            <select wire:model="modalActivityId"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Оберіть заняття</option>
                                @foreach($this->availableActivities->groupBy(fn($a) => $a->subject->name ?? '—') as $subjectName => $acts)
                                    <optgroup label="{{ $subjectName }}">
                                        @foreach($acts as $act)
                                            <option value="{{ $act->id }}">
                                                {{ $act->activity_type }} | {{ $act->teachers->pluck('name')->join(', ') }} | {{ $act->groups->pluck('name')->join(', ') }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        {{-- Room --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Аудиторія</label>
                            <select wire:model="modalRoomId"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Без аудиторії</option>
                                @foreach($this->rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->code }} — {{ $room->title }} ({{ $room->capacity }} місць)</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Day --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">День тижня</label>
                            <select wire:model="modalDayOfWeek"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="1">Понеділок</option>
                                <option value="2">Вівторок</option>
                                <option value="3">Середа</option>
                                <option value="4">Четвер</option>
                                <option value="5">П'ятниця</option>
                                <option value="6">Субота</option>
                                <option value="7">Неділя</option>
                            </select>
                        </div>

                        {{-- Slot --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Пара</label>
                            <select wire:model="modalSlotIndex"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @foreach($this->timeSlots as $slot)
                                    <option value="{{ $slot->slot_index }}">{{ $slot->slot_index }} пара ({{ $slot->start_time->format('H:i') }}-{{ $slot->end_time->format('H:i') }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Parity --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Парність</label>
                            <select wire:model="modalParity"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="both">Обидва тижні</option>
                                <option value="num">Чисельник</option>
                                <option value="den">Знаменник</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                        {{-- Delete button on the left --}}
                        <button wire:click="deleteAssignment({{ $editingAssignmentId }})"
                            wire:confirm="Видалити це заняття з розкладу?"
                            class="px-4 py-2 rounded-lg border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 text-sm font-medium transition">
                            🗑️ Видалити
                        </button>

                        <div class="flex gap-3">
                            <button wire:click="closeEditModal"
                                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                                Скасувати
                            </button>
                            <button wire:click="saveAssignment"
                                class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-medium shadow-sm">
                                Зберегти
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.focus()" @keydown.escape="$wire.closeCreateModal()">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeCreateModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 z-10">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Додати заняття</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ ['', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд'][$createDayOfWeek ?? 0] ?? '' }},
                            пара {{ $createSlotIndex }}
                        </p>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Activity --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Заняття</label>
                            <select wire:model="createActivityId"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Оберіть заняття</option>
                                @foreach($this->availableActivities->groupBy(fn($a) => $a->subject->name ?? '—') as $subjectName => $acts)
                                    <optgroup label="{{ $subjectName }}">
                                        @foreach($acts as $act)
                                            <option value="{{ $act->id }}">
                                                {{ $act->activity_type }} | {{ $act->teachers->pluck('name')->join(', ') }} | {{ $act->groups->pluck('name')->join(', ') }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        {{-- Room --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Аудиторія</label>
                            <select wire:model="createRoomId"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Без аудиторії</option>
                                @foreach($this->rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->code }} — {{ $room->title }} ({{ $room->capacity }} місць)</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Parity --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Парність</label>
                            <select wire:model="createParity"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="both">Обидва тижні</option>
                                <option value="num">Чисельник</option>
                                <option value="den">Знаменник</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeCreateModal"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                            Скасувати
                        </button>
                        <button wire:click="createAssignment"
                            class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-medium shadow-sm">
                            Додати
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Add Modal (for missing activities) --}}
    @if($showStatsAddModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.focus()" @keydown.escape="$wire.closeStatsAddModal()">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeStatsAddModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 z-10">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Додати заняття до слоту</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Оберіть слот для заняття, якого не вистачає
                        </p>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Day --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">День тижня</label>
                            <select wire:model="statsAddDayOfWeek"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Оберіть день</option>
                                <option value="1">Понеділок</option>
                                <option value="2">Вівторок</option>
                                <option value="3">Середа</option>
                                <option value="4">Четвер</option>
                                <option value="5">П'ятниця</option>
                                <option value="6">Субота</option>
                                <option value="7">Неділя</option>
                            </select>
                        </div>

                        {{-- Slot --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Пара</label>
                            <select wire:model="statsAddSlotIndex"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Оберіть пару</option>
                                @foreach($this->timeSlots as $slot)
                                    <option value="{{ $slot->slot_index }}">{{ $slot->slot_index }} пара ({{ $slot->start_time->format('H:i') }}-{{ $slot->end_time->format('H:i') }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Room --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Аудиторія</label>
                            <select wire:model="statsAddRoomId"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Без аудиторії</option>
                                @foreach($this->rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->code }} — {{ $room->title }} ({{ $room->capacity }} місць)</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Parity --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Парність</label>
                            <select wire:model="statsAddParity"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="both">Обидва тижні</option>
                                <option value="num">Чисельник</option>
                                <option value="den">Знаменник</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeStatsAddModal"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                            Скасувати
                        </button>
                        <button wire:click="createStatsAssignment"
                            class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-medium shadow-sm">
                            Додати
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Delete Modal (for excess activities) --}}
    @if($showStatsDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.focus()" @keydown.escape="$wire.closeStatsDeleteModal()">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeStatsDeleteModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 z-10">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Видалити зайве заняття</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Оберіть заняття для видалення (надлишок)
                        </p>
                    </div>

                    <div class="p-6">
                        @if(count($statsAssignmentsList) > 0)
                            <div class="space-y-2">
                                @foreach($statsAssignmentsList as $sa)
                                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-600 {{ $sa['locked'] ? 'opacity-50' : 'hover:bg-red-50 dark:hover:bg-red-900/10' }}">
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $sa['day_name'] }},</span>
                                            <span class="text-gray-600 dark:text-gray-300">{{ $sa['slot_index'] }} пара</span>
                                            <span class="text-gray-500 dark:text-gray-400">· {{ $sa['room'] }}</span>
                                            @if($sa['parity'] !== 'both')
                                                <span class="text-xs px-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                    {{ $sa['parity'] === 'num' ? 'Ч' : 'З' }}
                                                </span>
                                            @endif
                                            @if($sa['locked'])
                                                <span class="text-xs">🔒</span>
                                            @endif
                                        </div>
                                        @if(!$sa['locked'])
                                            <button wire:click="deleteStatsAssignment({{ $sa['id'] }})"
                                                wire:confirm="Видалити це заняття?"
                                                class="px-3 py-1 rounded-lg border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 text-xs font-medium transition">
                                                🗑️ Видалити
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-500 dark:text-gray-400 py-4">Немає призначених занять</p>
                        @endif
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button wire:click="closeStatsDeleteModal"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                            Закрити
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>