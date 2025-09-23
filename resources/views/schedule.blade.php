@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8" 
     x-data="scheduleApp()" 
     x-init="init()">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                📅 Розклад занять
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300">
                Оберіть курс, групу та тиждень для перегляду розкладу
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Course selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Курс
                    </label>
                    <select 
                        x-model="selectedCourse" 
                        @change="onCourseChange()"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Оберіть курс</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->number }} курс)</option>
                        @endforeach
                    </select>
                </div>

                <!-- Group selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Група
                    </label>
                    <select 
                        x-model="selectedGroup" 
                        @change="onGroupChange()"
                        :disabled="!selectedCourse || loadingGroups"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Оберіть групу</option>
                        <template x-for="group in groups" :key="group.id">
                            <option :value="group.id" x-text="group.name"></option>
                        </template>
                    </select>
                    <div x-show="loadingGroups" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                        Завантаження груп...
                    </div>
                </div>

                <!-- Date range selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Період
                    </label>
                    <div class="flex space-x-2">
                        <input 
                            type="date" 
                            x-model="startDate" 
                            @change="onDateRangeChange()"
                            :disabled="!selectedGroup || loadingSchedule"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="flex items-center text-gray-500 dark:text-gray-400">-</span>
                        <input 
                            type="date" 
                            x-model="endDate" 
                            @change="onDateRangeChange()"
                            :disabled="!selectedGroup || loadingSchedule"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                    </div>
                    <div x-show="dateRangeError" class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="dateRangeError"></div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Мінімум 1 день, максимум 2 тижні
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <!-- Table header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Розклад занять
                    <span x-show="selectedGroup" x-text="'для групи ' + getGroupName()"></span>
                </h2>
            </div>

            <!-- Loading state -->
            <div x-show="loadingSchedule" class="p-8 text-center">
                <div class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Завантаження розкладу...
                </div>
            </div>

            <!-- Schedule table -->
            <div x-show="!loadingSchedule && scheduleData" class="p-6">
                <x-schedule-table :editable="false" />
            </div>

            <!-- Message about selecting group and week -->
            <div x-show="!selectedGroup && !loadingSchedule" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <div class="text-lg mb-2">📚</div>
                <p>Оберіть курс та групу для перегляду розкладу</p>
            </div>
            
            <div x-show="selectedGroup && (!startDate || !endDate) && !loadingSchedule" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <div class="text-lg mb-2">📅</div>
                <p>Оберіть період для перегляду розкладу</p>
            </div>
        </div>
    </div>
</div>

<script>
function scheduleApp() {
    return {
        selectedCourse: '',
        selectedGroup: '',
        startDate: '',
        endDate: '',
        groups: [],
        scheduleData: null,
        dateRange: [],
        loadingGroups: false,
        loadingSchedule: false,
        dateRangeError: '',
        daysOfWeek: [1, 2, 3, 4, 5, 6, 7],
        timeSlots: {
            '08:00-09:30': '08:00-09:30',
            '09:45-11:15': '09:45-11:15',
            '11:30-13:00': '11:30-13:00',
            '13:15-14:45': '13:15-14:45',
            '15:00-16:30': '15:00-16:30',
            '16:45-18:15': '16:45-18:15',
            '18:30-20:00': '18:30-20:00'
        },
        subjectTypes: {},

        async init() {
            // Load current week as default
            await this.loadCurrentWeek();
        },

        async loadCurrentWeek() {
            try {
                const response = await fetch('/api/current-week');
                const data = await response.json();
                this.startDate = data.start_date;
                this.endDate = data.end_date;
            } catch (error) {
                console.error('Error loading current week:', error);
            }
        },

        async onCourseChange() {
            this.selectedGroup = '';
            this.scheduleData = null;
            
            if (!this.selectedCourse) {
                this.groups = [];
                return;
            }

            this.loadingGroups = true;
            try {
                const response = await fetch(`/api/courses/${this.selectedCourse}/groups`);
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
            
            if (this.selectedGroup && this.startDate && this.endDate) {
                await this.loadSchedule();
            }
        },

        async onDateRangeChange() {
            this.dateRangeError = '';
            
            if (!this.startDate || !this.endDate) {
                this.scheduleData = null;
                return;
            }

            // Validate date range
            const start = new Date(this.startDate);
            const end = new Date(this.endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 14) {
                this.dateRangeError = 'Діапазон дат не може перевищувати 2 тижні (14 днів)';
                this.scheduleData = null;
                return;
            }

            if (start > end) {
                this.dateRangeError = 'Дата початку не може бути пізніше дати закінчення';
                this.scheduleData = null;
                return;
            }

            if (this.selectedGroup) {
                await this.loadSchedule();
            }
        },

        async loadSchedule() {
            if (!this.selectedGroup || !this.startDate || !this.endDate) {
                console.log('loadSchedule: Missing required data', {
                    selectedGroup: this.selectedGroup,
                    startDate: this.startDate,
                    endDate: this.endDate
                });
                return;
            }

            this.loadingSchedule = true;
            console.log('loadSchedule: Starting to load schedule...');
            
            try {
                const url = `/api/groups/${this.selectedGroup}/schedule/${this.startDate}/${this.endDate}`;
                console.log('loadSchedule: Fetching URL:', url);
                
                const response = await fetch(url);
                console.log('loadSchedule: Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('loadSchedule: Received data:', data);
                
                this.scheduleData = data.schedule;
                this.dateRange = data.date_range;
                this.subjectTypes = data.subject_types;
                
                console.log('loadSchedule: Schedule data set:', this.scheduleData);
            } catch (error) {
                console.error('Error loading schedule:', error);
                this.scheduleData = null;
            } finally {
                this.loadingSchedule = false;
            }
        },

        getGroupName() {
            const group = this.groups.find(g => g.id == this.selectedGroup);
            return group ? group.name : '';
        },

        getDayName(day) {
            const dayNames = {
                1: 'Пн', 2: 'Вт', 3: 'Ср', 4: 'Чт', 
                5: 'Пт', 6: 'Сб', 7: 'Нд'
            };
            return dayNames[day] || day;
        },

        getScheduleItem(date, timeSlot) {
            return this.scheduleData?.[date]?.[timeSlot] || null;
        },

        getSubjectColor(subjectType) {
            const colors = {
                'lecture': 'bg-gradient-to-r from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800 text-blue-800 dark:text-blue-100 border-l-4 border-blue-500',
                'practice': 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900 dark:to-green-800 text-green-800 dark:text-green-100 border-l-4 border-green-500'
            };
            return colors[subjectType] || 'bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-800 dark:text-gray-200 border-l-4 border-gray-400';
        }
    }
}
</script>
@endsection
