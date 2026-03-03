@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8" x-data="publicScheduleApp()" x-init="init()">

        <!-- Header with Tenant Branding -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $tenant->name }}
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
                        <div class="relative">
                            <select x-model="selectedCourse" @change="onCourseChange()"
                                class="w-full appearance-none px-4 py-3 pr-10 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-base">
                                <option value="">Оберіть курс</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->number }} курс)</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Group selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Група
                        </label>
                        <div class="relative">
                            <select x-model="selectedGroup" @change="onGroupChange()"
                                :disabled="!selectedCourse || loadingGroups"
                                class="w-full appearance-none px-4 py-3 pr-10 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed transition-all text-base">
                                <option value="">Оберіть групу</option>
                                <template x-for="group in groups" :key="group.id">
                                    <option :value="group.id" x-text="group.name"></option>
                                </template>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div x-show="loadingGroups" class="mt-2 text-sm text-indigo-600 dark:text-indigo-400">
                            Завантаження груп...
                        </div>
                    </div>

                    <!-- Week navigation -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Тиждень
                        </label>
                        <div class="flex items-center gap-2">
                            <button @click="prevWeek()" :disabled="!canGoPrev()"
                                class="flex-shrink-0 p-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm"
                                title="Попередній тиждень">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div class="flex-1 text-center px-3 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="weekLabel()"></span>
                            </div>

                            <button @click="nextWeek()" :disabled="!canGoNext()"
                                class="flex-shrink-0 p-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm"
                                title="Наступний тиждень">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2 text-center">
                            <button @click="goToCurrentWeek()" :disabled="isCurrentWeek()"
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 disabled:opacity-40 disabled:cursor-default transition-colors">
                                Поточний тиждень
                            </button>
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
                                        <template x-if="day.parity && day.parity !== 'both'">
                                            <div class="mt-0.5">
                                                <span x-show="day.parity === 'num'" class="text-[10px] font-medium text-indigo-500 dark:text-indigo-400">чис.</span>
                                                <span x-show="day.parity === 'den'" class="text-[10px] font-medium text-amber-500 dark:text-amber-400">знам.</span>
                                            </div>
                                        </template>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(slotTime, slotIdx) in timeSlots" :key="slotIdx">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
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
                                                    <template x-if="getScheduleItem(day.date, slotIdx).parity && getScheduleItem(day.date, slotIdx).parity !== 'both'">
                                                        <div class="text-[10px] opacity-60 mt-1"
                                                            x-text="getScheduleItem(day.date, slotIdx).parity === 'num' ? 'чис.' : 'знам.'">
                                                        </div>
                                                    </template>
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
                    <div class="text-5xl mb-4">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <p class="text-lg">Оберіть курс та групу для перегляду розкладу</p>
                </div>

                <div x-show="scheduleMessage" class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="text-5xl mb-4">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <p class="text-lg" x-text="scheduleMessage"></p>
                </div>

                <!-- Legend -->
                <div x-show="!loadingSchedule && scheduleData && dateRange.length > 0"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex flex-wrap gap-4 items-center">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Типи
                            занять:</span>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm bg-blue-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Лекція</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm bg-green-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Практика</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm bg-amber-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Лабораторна</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm bg-red-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Семінар</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm bg-purple-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">ПК</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function publicScheduleApp() {
            return {
                selectedCourse: '',
                selectedGroup: '',
                weekStart: null,   // Date object: Monday of current selected week
                calendarMinDate: '{{ $calendar?->start_date?->format("Y-m-d") ?? "" }}',
                calendarMaxDate: '{{ $calendar?->end_date?->format("Y-m-d") ?? "" }}',
                currentWeekMonday: null,  // Date object: Monday of actual current week
                groups: [],
                scheduleData: null,
                dateRange: [],
                timeSlots: {},
                versionInfo: null,
                loadingGroups: false,
                loadingSchedule: false,
                scheduleMessage: '',
                slug: '{{ $slug }}',

                init() {
                    const today = new Date();
                    this.currentWeekMonday = this.getMondayOf(today);
                    this.weekStart = new Date(this.currentWeekMonday);

                    // Clamp to calendar range
                    if (this.calendarMinDate && this.calendarMaxDate) {
                        const calStart = new Date(this.calendarMinDate);
                        const calEnd = new Date(this.calendarMaxDate);

                        if (today > calEnd || today < calStart) {
                            this.weekStart = this.getMondayOf(calStart);
                        }
                    }
                },

                getMondayOf(date) {
                    const d = new Date(date);
                    const day = d.getDay() === 0 ? 7 : d.getDay();
                    d.setDate(d.getDate() - (day - 1));
                    d.setHours(0, 0, 0, 0);
                    return d;
                },

                getSundayOf(mondayDate) {
                    const d = new Date(mondayDate);
                    d.setDate(d.getDate() + 6);
                    return d;
                },

                getEffectiveStart() {
                    let start = new Date(this.weekStart);
                    if (this.calendarMinDate) {
                        const calStart = new Date(this.calendarMinDate);
                        if (start < calStart) start = calStart;
                    }
                    return start;
                },

                getEffectiveEnd() {
                    let end = this.getSundayOf(this.weekStart);
                    if (this.calendarMaxDate) {
                        const calEnd = new Date(this.calendarMaxDate);
                        if (end > calEnd) end = calEnd;
                    }
                    return end;
                },

                weekLabel() {
                    const start = this.getEffectiveStart();
                    const end = this.getEffectiveEnd();
                    return this.formatDD_MM(start) + ' — ' + this.formatDD_MM(end);
                },

                formatDD_MM(date) {
                    const d = String(date.getDate()).padStart(2, '0');
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    return d + '.' + m;
                },

                formatDateISO(date) {
                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const d = String(date.getDate()).padStart(2, '0');
                    return `${y}-${m}-${d}`;
                },

                canGoPrev() {
                    if (!this.calendarMinDate) return true;
                    const calStart = new Date(this.calendarMinDate);
                    const prevMonday = new Date(this.weekStart);
                    prevMonday.setDate(prevMonday.getDate() - 7);
                    const prevSunday = this.getSundayOf(prevMonday);
                    return prevSunday >= calStart;
                },

                canGoNext() {
                    if (!this.calendarMaxDate) return true;
                    const calEnd = new Date(this.calendarMaxDate);
                    const nextMonday = new Date(this.weekStart);
                    nextMonday.setDate(nextMonday.getDate() + 7);
                    return nextMonday <= calEnd;
                },

                prevWeek() {
                    if (!this.canGoPrev()) return;
                    this.weekStart.setDate(this.weekStart.getDate() - 7);
                    this.weekStart = new Date(this.weekStart);
                    if (this.selectedGroup) this.loadSchedule();
                },

                nextWeek() {
                    if (!this.canGoNext()) return;
                    this.weekStart.setDate(this.weekStart.getDate() + 7);
                    this.weekStart = new Date(this.weekStart);
                    if (this.selectedGroup) this.loadSchedule();
                },

                isCurrentWeek() {
                    return this.weekStart && this.currentWeekMonday &&
                        this.weekStart.getTime() === this.currentWeekMonday.getTime();
                },

                goToCurrentWeek() {
                    if (this.isCurrentWeek()) return;
                    this.weekStart = new Date(this.currentWeekMonday);

                    // Clamp if outside calendar range
                    if (this.calendarMinDate && this.calendarMaxDate) {
                        const calStart = new Date(this.calendarMinDate);
                        const calEnd = new Date(this.calendarMaxDate);
                        const today = new Date();
                        if (today > calEnd || today < calStart) {
                            this.weekStart = this.getMondayOf(calStart);
                        }
                    }

                    if (this.selectedGroup) this.loadSchedule();
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
                    if (this.selectedGroup) {
                        await this.loadSchedule();
                    }
                },

                async loadSchedule() {
                    if (!this.selectedGroup || !this.weekStart) return;

                    this.loadingSchedule = true;
                    this.scheduleMessage = '';

                    const startDate = this.formatDateISO(this.getEffectiveStart());
                    const endDate = this.formatDateISO(this.getEffectiveEnd());

                    try {
                        const url = `/s/${this.slug}/api/schedule/${this.selectedGroup}/${startDate}/${endDate}`;
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
