@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8" x-data="publicScheduleApp()" x-init="init()">

        <!-- Header with Tenant Branding -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    📅 {{ $tenant->name }}
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300">
                    Розклад занять
                </p>
                @if($calendar)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $calendar->name }} ({{ $calendar->start_date->format('d.m.Y') }} —
                        {{ $calendar->end_date->format('d.m.Y') }})
                    </p>
                @endif
            </div>
        </div>

        <!-- Filters -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Course selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Курс
                        </label>
                        <select x-model="selectedCourse" @change="onCourseChange()"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all">
                            <option value="">Оберіть курс</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->number }} курс)</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Group selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Група
                        </label>
                        <select x-model="selectedGroup" @change="onGroupChange()"
                            :disabled="!selectedCourse || loadingGroups"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <option value="">Оберіть групу</option>
                            <template x-for="group in groups" :key="group.id">
                                <option :value="group.id" x-text="group.name"></option>
                            </template>
                        </select>
                        <div x-show="loadingGroups" class="mt-2 text-sm text-indigo-600 dark:text-indigo-400">
                            Завантаження груп...
                        </div>
                    </div>

                    <!-- Date range -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Період
                        </label>
                        <div class="flex space-x-2">
                            <input type="date" x-model="startDate" @change="onDateRangeChange()" :disabled="!selectedGroup"
                                :min="calendarMinDate" :max="calendarMaxDate"
                                class="flex-1 px-3 py-3 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white disabled:opacity-50 transition-all">
                            <span class="flex items-center text-gray-400">—</span>
                            <input type="date" x-model="endDate" @change="onDateRangeChange()" :disabled="!selectedGroup"
                                :min="calendarMinDate" :max="calendarMaxDate"
                                class="flex-1 px-3 py-3 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white disabled:opacity-50 transition-all">
                        </div>
                        <div x-show="calendarMinDate" class="mt-1 text-xs text-gray-400">
                            Доступний період: <span x-text="formatDisplayDate(calendarMinDate)"></span> — <span
                                x-text="formatDisplayDate(calendarMaxDate)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <!-- Version info -->
                <template x-if="versionInfo">
                    <div
                        class="px-6 py-3 bg-indigo-50 dark:bg-indigo-900/30 border-b border-indigo-100 dark:border-indigo-800">
                        <p class="text-sm text-indigo-700 dark:text-indigo-300">
                            <span class="font-semibold" x-text="versionInfo.name"></span>
                            <span x-show="versionInfo.published_at">
                                — опубліковано <span x-text="versionInfo.published_at"></span>
                            </span>
                        </p>
                    </div>
                </template>

                <!-- Loading state -->
                <div x-show="loadingSchedule" class="p-12 text-center">
                    <div class="inline-flex items-center text-indigo-600 dark:text-indigo-400">
                        <svg class="animate-spin -ml-1 mr-3 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="text-lg">Завантаження розкладу...</span>
                    </div>
                </div>

                <!-- Schedule Table -->
                <div x-show="!loadingSchedule && scheduleData && dateRange.length > 0" class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th
                                    class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b dark:border-gray-600 w-24">
                                    Пара
                                </th>
                                <template x-for="day in dateRange" :key="day.date">
                                    <th
                                        class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b dark:border-gray-600 min-w-[180px]">
                                        <div x-text="day.day_name"></div>
                                        <div class="text-xs font-normal mt-0.5" x-text="day.formatted"></div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(slotTime, slotIdx) in timeSlots" :key="slotIdx">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td
                                        class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 whitespace-nowrap">
                                        <div class="text-xs text-gray-500" x-text="'Пара ' + slotIdx"></div>
                                        <div x-text="slotTime"></div>
                                    </td>
                                    <template x-for="day in dateRange" :key="day.date + '_' + slotIdx">
                                        <td class="px-2 py-2 border-b dark:border-gray-700">
                                            <template x-if="getScheduleItem(day.date, slotIdx)">
                                                <div :class="getSubjectColor(getScheduleItem(day.date, slotIdx).subject_type)"
                                                    class="rounded-xl p-3 text-sm shadow-sm transition-transform hover:scale-[1.02]">
                                                    <div class="font-semibold mb-1"
                                                        x-text="getScheduleItem(day.date, slotIdx).subject"></div>
                                                    <div class="text-xs opacity-80 flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        <span x-text="getScheduleItem(day.date, slotIdx).teacher"></span>
                                                    </div>
                                                    <div class="text-xs opacity-80 flex items-center gap-1 mt-0.5"
                                                        x-show="getScheduleItem(day.date, slotIdx).classroom">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                        <span x-text="getScheduleItem(day.date, slotIdx).classroom"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty states -->
                <div x-show="!selectedGroup && !loadingSchedule" class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="text-5xl mb-4">📚</div>
                    <p class="text-lg">Оберіть курс та групу для перегляду розкладу</p>
                </div>

                <div x-show="scheduleMessage" class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="text-5xl mb-4">📋</div>
                    <p class="text-lg" x-text="scheduleMessage"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function publicScheduleApp() {
            return {
                selectedCourse: '',
                selectedGroup: '',
                startDate: '',
                endDate: '',
                calendarMinDate: '{{ $calendar?->start_date?->format("Y-m-d") ?? "" }}',
                calendarMaxDate: '{{ $calendar?->end_date?->format("Y-m-d") ?? "" }}',
                groups: [],
                scheduleData: null,
                dateRange: [],
                timeSlots: {},
                versionInfo: null,
                loadingGroups: false,
                loadingSchedule: false,
                scheduleMessage: '',
                slug: '{{ $slug }}',

                async init() {
                    const today = new Date();
                    const dayOfWeek = today.getDay() === 0 ? 7 : today.getDay();
                    const monday = new Date(today);
                    monday.setDate(today.getDate() - (dayOfWeek - 1));
                    const sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);

                    let start = monday;
                    let end = sunday;

                    // Clamp to calendar range if available
                    if (this.calendarMinDate && this.calendarMaxDate) {
                        const calStart = new Date(this.calendarMinDate);
                        const calEnd = new Date(this.calendarMaxDate);

                        // If today is outside calendar, snap to first week of calendar
                        if (today > calEnd || today < calStart) {
                            const calDayOfWeek = calStart.getDay() === 0 ? 7 : calStart.getDay();
                            start = new Date(calStart);
                            start.setDate(calStart.getDate() - (calDayOfWeek - 1));
                            end = new Date(start);
                            end.setDate(start.getDate() + 6);

                            if (start < calStart) start = calStart;
                            if (end > calEnd) end = calEnd;
                        }
                    }

                    this.startDate = this.formatDateISO(start);
                    this.endDate = this.formatDateISO(end);
                },

                formatDateISO(date) {
                    return date.toISOString().split('T')[0];
                },

                formatDisplayDate(dateStr) {
                    if (!dateStr) return '';
                    const parts = dateStr.split('-');
                    return `${parts[2]}.${parts[1]}.${parts[0]}`;
                },

                async onCourseChange() {
                    this.selectedGroup = '';
                    this.scheduleData = null;
                    this.scheduleMessage = '';
                    this.versionInfo = null;

                    if (!this.selectedCourse) {
                        this.groups = [];
                        return;
                    }

                    this.loadingGroups = true;
                    try {
                        const response = await fetch(`/s/${this.slug}/api/groups/${this.selectedCourse}`);
                        this.groups = await response.json();
                    } catch (error) {
                        console.error('Error loading groups:', error);
                        this.groups = [];
                    } finally {
                        this.loadingGroups = false;
                    }
                },

                async onGroupChange() {
                    this.scheduleData = null;
                    this.scheduleMessage = '';
                    if (this.selectedGroup && this.startDate && this.endDate) {
                        await this.loadSchedule();
                    }
                },

                async onDateRangeChange() {
                    if (this.selectedGroup && this.startDate && this.endDate) {
                        await this.loadSchedule();
                    }
                },

                async loadSchedule() {
                    if (!this.selectedGroup || !this.startDate || !this.endDate) return;

                    this.loadingSchedule = true;
                    this.scheduleMessage = '';

                    try {
                        const url = `/s/${this.slug}/api/schedule/${this.selectedGroup}/${this.startDate}/${this.endDate}`;
                        const response = await fetch(url);
                        const data = await response.json();

                        if (data.message) {
                            this.scheduleMessage = data.message;
                            this.scheduleData = null;
                            this.dateRange = [];
                        } else {
                            this.scheduleData = data.schedule;
                            this.dateRange = data.date_range;
                            this.timeSlots = data.time_slots;
                            this.versionInfo = data.version;
                        }

                        if (data.calendar_range) {
                            this.calendarMinDate = data.calendar_range.start;
                            this.calendarMaxDate = data.calendar_range.end;
                        }
                    } catch (error) {
                        console.error('Error loading schedule:', error);
                        this.scheduleMessage = 'Помилка завантаження розкладу';
                    } finally {
                        this.loadingSchedule = false;
                    }
                },

                getScheduleItem(date, slotIdx) {
                    return this.scheduleData?.[date]?.[slotIdx] || null;
                },

                getSubjectColor(type) {
                    const colors = {
                        'lecture': 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/40 dark:to-blue-800/40 text-blue-900 dark:text-blue-100 border-l-4 border-blue-500',
                        'practice': 'bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/40 dark:to-green-800/40 text-green-900 dark:text-green-100 border-l-4 border-green-500',
                        'lab': 'bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/40 dark:to-amber-800/40 text-amber-900 dark:text-amber-100 border-l-4 border-amber-500',
                        'seminar': 'bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/40 dark:to-red-800/40 text-red-900 dark:text-red-100 border-l-4 border-red-500',
                        'pc': 'bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/40 dark:to-purple-800/40 text-purple-900 dark:text-purple-100 border-l-4 border-purple-500',
                    };
                    return colors[type] || 'bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 text-gray-900 dark:text-gray-200 border-l-4 border-gray-400';
                }
            }
        }
    </script>
@endsection