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

                <!-- Week selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Тиждень
                    </label>
                    <select 
                        x-model="selectedWeek" 
                        @change="onWeekChange()"
                        :disabled="!selectedGroup || loadingWeeks"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Оберіть тиждень</option>
                        <template x-for="week in weeks" :key="week.number">
                            <option :value="week.number" x-text="week.label"></option>
                        </template>
                    </select>
                    <div x-show="loadingWeeks" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                        Завантаження тижнів...
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
            
            <div x-show="selectedGroup && !selectedWeek && !loadingSchedule" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <div class="text-lg mb-2">📅</div>
                <p>Оберіть тиждень для перегляду розкладу</p>
            </div>
        </div>
    </div>
</div>

<script>
function scheduleApp() {
    return {
        selectedCourse: '',
        selectedGroup: '',
        selectedWeek: '',
        groups: [],
        weeks: [],
        scheduleData: null,
        loadingGroups: false,
        loadingWeeks: false,
        loadingSchedule: false,
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

        init() {
            // Initialization
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
            this.selectedWeek = '';
            
            if (this.selectedGroup) {
                await this.loadWeeks();
            }
        },

        async onWeekChange() {
            if (this.selectedGroup && this.selectedWeek) {
                await this.loadSchedule();
            }
        },

        async loadWeeks() {
            this.loadingWeeks = true;
            try {
                const response = await fetch('/api/weeks');
                this.weeks = await response.json();
            } catch (error) {
                console.error('Error loading weeks:', error);
                this.weeks = [];
            } finally {
                this.loadingWeeks = false;
            }
        },

        async loadSchedule() {
            if (!this.selectedGroup || !this.selectedWeek) return;

            this.loadingSchedule = true;
            try {
                const url = `/api/groups/${this.selectedGroup}/schedule/${this.selectedWeek}`;
                const response = await fetch(url);
                const data = await response.json();
                
                this.scheduleData = data.schedule;
                // Don't overwrite daysOfWeek and timeSlots, they are already initialized
                this.subjectTypes = data.subject_types;
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

        getScheduleItem(day, timeSlot) {
            return this.scheduleData?.[day]?.[timeSlot] || null;
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
