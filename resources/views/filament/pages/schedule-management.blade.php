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
                                        <div>{{ substr($slot->start_time, 0, 5) }}-{{ substr($slot->end_time, 0, 5) }}</div>
                                    </td>
                                    @foreach($data['dateRange'] as $day)
                                        @php $item = $data['matrix'][$day['date']][$slot->slot_index] ?? null; @endphp
                                        <td class="px-1 py-1 border-b dark:border-gray-700 schedule-cell">
                                            @if($item)
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
                                            @else
                                                <div class="rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 p-2 text-center text-gray-400 dark:text-gray-500 text-xs h-12 flex items-center justify-center">
                                                    —
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
                        @php
                            $editingAssignment = $editingAssignmentId ? \App\Models\ScheduleAssignment::with('activity.subject', 'activity.teachers', 'activity.groups')->find($editingAssignmentId) : null;
                        @endphp
                        @if($editingAssignment)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $editingAssignment->activity?->subject?->name ?? '—' }}
                                | {{ $editingAssignment->activity?->teachers?->pluck('name')->join(', ') }}
                                | {{ $editingAssignment->activity?->groups?->pluck('name')->join(', ') }}
                            </p>
                        @endif
                    </div>

                    <div class="p-6 space-y-4">
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
                                    <option value="{{ $slot->slot_index }}">{{ $slot->slot_index }} пара ({{ substr($slot->start_time, 0, 5) }}-{{ substr($slot->end_time, 0, 5) }})</option>
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

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
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
    @endif
</x-filament-panels::page>