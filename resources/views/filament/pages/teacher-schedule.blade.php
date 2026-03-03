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
        {{-- Week Navigation --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="max-w-sm mx-auto">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 text-center">Тиждень</label>
                <div class="flex items-center gap-2">
                    <button wire:click="prevWeek" @if(!$this->canGoPrev) disabled @endif
                        class="flex-shrink-0 p-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm"
                        title="Попередній тиждень">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    <div class="flex-1 text-center px-3 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm whitespace-nowrap">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $weekLabel }}</span>
                        @if($this->weekParityLabel)
                            <span class="text-xs font-medium {{ $this->weekParityLabel === 'Чисельник' ? 'text-indigo-500 dark:text-indigo-400' : 'text-amber-500 dark:text-amber-400' }}">
                                ({{ $this->weekParityLabel === 'Чисельник' ? 'Ч' : 'З' }})
                            </span>
                        @endif
                    </div>

                    <button wire:click="nextWeek" @if(!$this->canGoNext) disabled @endif
                        class="flex-shrink-0 p-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm"
                        title="Наступний тиждень">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                <div class="mt-2 text-center">
                    <button wire:click="currentWeek"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                        Поточний тиждень
                    </button>
                </div>
            </div>
        </div>

        {{-- Schedule Table --}}
        @php $data = $this->scheduleData; @endphp

        @if($this->publishedVersion && !empty($data['dateRange']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $this->publishedVersion->name }}
                    </h2>
                    <p class="text-sm text-gray-500">Натисніть на заняття, щоб запропонувати перенос</p>
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
                                        @if(!empty($day['parity']) && $day['parity'] !== 'both')
                                            <div class="text-[10px] font-medium {{ $day['parity'] === 'num' ? 'text-indigo-500 dark:text-indigo-400' : 'text-amber-500 dark:text-amber-400' }}">
                                                {{ $day['parity'] === 'num' ? 'чис.' : 'знам.' }}
                                            </div>
                                        @endif
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
                                                    wire:click="openRescheduleModal({{ $item['id'] }})">
                                                    <div class="font-semibold truncate">
                                                        {{ $item['subject'] }}
                                                        @if($item['parity'] !== 'both')
                                                            <span class="inline-flex items-center ml-0.5 px-1 py-px rounded text-[9px] font-medium leading-none
                                                                {{ $item['parity'] === 'num'
                                                                    ? 'bg-indigo-200/60 text-indigo-800 dark:bg-indigo-400/20 dark:text-indigo-300'
                                                                    : 'bg-amber-200/60 text-amber-800 dark:bg-amber-400/20 dark:text-amber-300' }}">
                                                                {{ $item['parity'] === 'num' ? 'Ч' : 'З' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="truncate opacity-80">{{ $item['groups'] }}</div>
                                                    @if($item['room'])
                                                        <div class="truncate opacity-70 text-[10px]">{{ $item['room'] }}</div>
                                                    @endif
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
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-500 dark:text-gray-400">
                <div class="text-5xl mb-4">📋</div>
                <p class="text-lg">Наразі немає опублікованого розкладу</p>
            </div>
        @endif

        {{-- My Reschedule Requests --}}
        @if($this->myRequests->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Мої заявки на перенос</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Предмет</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Запропонований час</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Статус</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Коментар адміна</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->myRequests as $req)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-2">{{ $req->assignment?->activity?->subject?->name ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        {{ \App\Models\TeacherPreferenceRule::DAY_NAMES[$req->proposed_day_of_week] ?? '' }},
                                        пара {{ $req->proposed_slot_index }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ match($req->status) {
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                default => 'bg-gray-100 text-gray-800',
                                            } }}">
                                            {{ match($req->status) { 'pending' => 'Очікує', 'approved' => 'Затверджено', 'rejected' => 'Відхилено', default => $req->status } }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $req->admin_comment ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $req->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Reschedule Modal --}}
    @if($showRescheduleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.focus()" @keydown.escape="$wire.closeRescheduleModal()">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeRescheduleModal"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 z-10">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Запропонувати перенос</h3>
                        @php
                            $assignment = $rescheduleAssignmentId ? \App\Models\ScheduleAssignment::with('activity.subject', 'activity.groups')->find($rescheduleAssignmentId) : null;
                        @endphp
                        @if($assignment)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $assignment->activity?->subject?->name ?? '—' }}
                                | {{ $assignment->activity?->groups?->pluck('name')->join(', ') }}
                            </p>
                        @endif
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Бажаний день</label>
                            <select wire:model="proposedDayOfWeek"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="1">Понеділок</option>
                                <option value="2">Вівторок</option>
                                <option value="3">Середа</option>
                                <option value="4">Четвер</option>
                                <option value="5">П'ятниця</option>
                                <option value="6">Субота</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Бажана пара</label>
                            <select wire:model="proposedSlotIndex"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @foreach($this->timeSlots as $slot)
                                    <option value="{{ $slot->slot_index }}">{{ $slot->slot_index }} пара ({{ substr($slot->start_time, 0, 5) }}-{{ substr($slot->end_time, 0, 5) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Парність</label>
                            <select wire:model="proposedParity"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="both">Обидва тижні</option>
                                <option value="num">Чисельник</option>
                                <option value="den">Знаменник</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Аудиторія (необов'язково)</label>
                            <select wire:model="proposedRoomId"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Без змін</option>
                                @foreach($this->rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->code }} — {{ $room->title }} ({{ $room->capacity }} місць)</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Коментар</label>
                            <textarea wire:model="teacherComment" rows="3"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Причина переносу..."></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeRescheduleModal"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium">
                            Скасувати
                        </button>
                        <button wire:click="submitReschedule"
                            class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-medium shadow-sm">
                            Надіслати заявку
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
