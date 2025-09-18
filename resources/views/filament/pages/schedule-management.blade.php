<x-filament-panels::page>
        <style>
            /* Принудительно перезаписываем стили Filament */
            .schedule-item-lecture {
                background: linear-gradient(to right, #dbeafe, #bfdbfe) !important;
                color: #1e40af !important;
                border-left: 4px solid #3b82f6 !important;
            }

            .dark .schedule-item-lecture {
                background: linear-gradient(to right, #1e3a8a, #1e40af) !important;
                color: #dbeafe !important;
                border-left: 4px solid #3b82f6 !important;
            }

            .schedule-item-practice {
                background: linear-gradient(to right, #dcfce7, #bbf7d0) !important;
                color: #166534 !important;
                border-left: 4px solid #22c55e !important;
            }

            .dark .schedule-item-practice {
                background: linear-gradient(to right, #14532d, #166534) !important;
                color: #dcfce7 !important;
                border-left: 4px solid #22c55e !important;
            }

            .schedule-item-default {
                background: linear-gradient(to right, #f3f4f6, #e5e7eb) !important;
                color: #374151 !important;
                border-left: 4px solid #6b7280 !important;
            }

            .dark .schedule-item-default {
                background: linear-gradient(to right, #374151, #4b5563) !important;
                color: #e5e7eb !important;
                border-left: 4px solid #6b7280 !important;
            }

            /* Перезаписываем все возможные стили Filament */
            .schedule-item-lecture *,
            .schedule-item-practice *,
            .schedule-item-default * {
                color: inherit !important;
            }

            .schedule-item-lecture:hover,
            .schedule-item-practice:hover,
            .schedule-item-default:hover {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            }

            /* Убираем выделение строк при наведении */
            .schedule-table tr:hover {
                background-color: transparent !important;
            }

            .schedule-table tr:hover td {
                background-color: transparent !important;
            }

            /* Исправляем стили тултипов - перебиваем Filament */
            [title] {
                position: relative !important;
            }

            /* Стили для тултипов в светлой теме */
            .schedule-item-lecture[title]:hover::after,
            .schedule-item-practice[title]:hover::after,
            .schedule-item-default[title]:hover::after {
                content: attr(title) !important;
                position: absolute !important;
                bottom: 100% !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                background-color: #1f2937 !important;
                color: #f9fafb !important;
                padding: 8px 12px !important;
                border-radius: 6px !important;
                font-size: 12px !important;
                white-space: pre-line !important;
                z-index: 1000 !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                border: 1px solid #374151 !important;
                max-width: 200px !important;
                word-wrap: break-word !important;
            }

            /* Стили для тултипов в темной теме */
            .dark .schedule-item-lecture[title]:hover::after,
            .dark .schedule-item-practice[title]:hover::after,
            .dark .schedule-item-default[title]:hover::after {
                background-color: #f9fafb !important;
                color: #1f2937 !important;
                border: 1px solid #d1d5db !important;
            }

            /* Убираем стандартные тултипы браузера */
            .schedule-item-lecture[title],
            .schedule-item-practice[title],
            .schedule-item-default[title] {
                text-decoration: none !important;
            }

            /* Исправляем стили кнопок в модальном окне */
            .modal-button-save {
                background-color: #2563eb !important;
                color: #ffffff !important;
                border-color: #2563eb !important;
            }

            .dark .modal-button-save {
                background-color: #3b82f6 !important;
                color: #ffffff !important;
                border-color: #3b82f6 !important;
            }

            .modal-button-cancel {
                background-color: #ffffff !important;
                color: #374151 !important;
                border-color: #d1d5db !important;
            }

            .dark .modal-button-cancel {
                background-color: #4b5563 !important;
                color: #e5e7eb !important;
                border-color: #6b7280 !important;
            }
        </style>
    
    <div class="space-y-6" x-data="scheduleManagementApp()" x-init="init()">
        <!-- Форма фильтров -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Фильтры расписания
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Выбор курса -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Курс
                    </label>
                    <select x-model="selectedCourse" 
                            @change="onCourseChange()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Выберите курс</option>
                        <template x-for="course in courses" :key="course.id">
                            <option :value="course.id" x-text="course.name + ' (' + course.number + ' курс)'"></option>
                        </template>
                    </select>
                </div>

                <!-- Выбор группы -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Группа
                    </label>
                    <select x-model="selectedGroup" 
                            @change="onGroupChange()"
                            :disabled="!selectedCourse || loadingGroups"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Выберите группу</option>
                        <template x-for="group in groups" :key="group.id">
                            <option :value="group.id" x-text="group.name"></option>
                        </template>
                    </select>
                    <div x-show="loadingGroups" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                        Загрузка групп...
                    </div>
                </div>

                <!-- Выбор недели -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Неделя
                    </label>
                    <select x-model="selectedWeek" 
                            @change="onWeekChange()"
                            :disabled="!selectedGroup || loadingWeeks"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Выберите неделю</option>
                        <template x-for="week in weeks" :key="week.number">
                            <option :value="week.number" x-text="week.label"></option>
                        </template>
                    </select>
                    <div x-show="loadingWeeks" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                        Загрузка недель...
                    </div>
                </div>
            </div>
        </div>

        <!-- Интерактивная таблица расписания -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Управление расписанием
                    <span x-show="selectedGroup" x-text="'для группы ' + getGroupName()"></span>
                </h2>
            </div>

            <!-- Загрузочное состояние -->
            <div x-show="loadingSchedule" class="p-8 text-center">
                <div class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Загрузка расписания...
                </div>
            </div>

                <!-- Таблица расписания -->
                <div x-show="!loadingSchedule && scheduleData" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="schedule-table min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                                        Время
                                    </th>
                                    <template x-for="day in daysOfWeek" :key="day">
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                            <span x-text="getDayName(day)"></span>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="timeSlot in timeSlots" :key="timeSlot">
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white" x-text="timeSlot"></td>
                                        <template x-for="day in daysOfWeek" :key="day">
                                            <td class="px-2 py-3 text-center">
                                                <div x-show="getScheduleItem(day, timeSlot)" 
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100"
                                                     class="p-2 rounded-lg text-xs shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
                                                     :class="getSubjectClass(getScheduleItem(day, timeSlot)?.subject_type)"
                                                     :title="'Преподаватель: ' + getScheduleItem(day, timeSlot)?.teacher + (getScheduleItem(day, timeSlot)?.classroom ? '\\nАудитория: ' + getScheduleItem(day, timeSlot)?.classroom : '')"
                                                     @click="openEditModal(getScheduleItem(day, timeSlot)?.id, getScheduleItem(day, timeSlot)?.subject, getScheduleItem(day, timeSlot)?.teacher, getScheduleItem(day, timeSlot)?.classroom || '', day, timeSlot, getScheduleItem(day, timeSlot)?.week_number)">
                                                    <div class="font-medium truncate" x-text="getScheduleItem(day, timeSlot)?.subject"></div>
                                                    <div class="truncate" x-text="getScheduleItem(day, timeSlot)?.teacher"></div>
                                                    <div x-show="getScheduleItem(day, timeSlot)?.classroom" 
                                                         class="truncate" 
                                                         x-text="'Ауд. ' + getScheduleItem(day, timeSlot)?.classroom"></div>
                                                    <div class="text-xs opacity-50 mt-1">Клик для редактирования</div>
                                                </div>
                                                <div x-show="!getScheduleItem(day, timeSlot)" 
                                                     class="p-2 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 text-center cursor-pointer hover:border-blue-400 hover:text-blue-500 transition-colors"
                                                     @click="openAddModal(day, timeSlot)">
                                                    <div class="text-xs">+ Добавить занятие</div>
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- Сообщение о выборе группы и недели -->
            <div x-show="!selectedGroup && !loadingSchedule" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <div class="text-lg mb-2">📚</div>
                <p>Выберите курс и группу для управления расписанием</p>
            </div>
            
            <div x-show="selectedGroup && !selectedWeek && !loadingSchedule" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <div class="text-lg mb-2">📅</div>
                <p>Выберите неделю для управления расписанием</p>
            </div>
        </div>

        <!-- Модальное окно для редактирования/добавления занятия -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-600"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                <form @submit.prevent="saveLesson()">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" x-text="isEditing ? 'Редактировать занятие' : 'Добавить занятие'"></h3>
                                <div class="mt-4 space-y-4">
                                    <!-- Предмет -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Предмет
                                        </label>
                                        <select x-model="formData.subject_id"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                                required>
                                            <option value="">Выберите предмет</option>
                                            <template x-for="subject in subjects" :key="subject.id">
                                                <option :value="subject.id" x-text="subject.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Преподаватель -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Преподаватель
                                        </label>
                                        <select x-model="formData.teacher_id"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                                required>
                                            <option value="">Выберите преподавателя</option>
                                            <template x-for="teacher in teachers" :key="teacher.id">
                                                <option :value="teacher.id" x-text="teacher.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Аудитория -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Аудитория
                                        </label>
                                        <input type="text"
                                               x-model="formData.classroom"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                               placeholder="Например: 101">
                                    </div>

                                    <!-- День недели (только для добавления) -->
                                    <div x-show="!isEditing">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            День недели
                                        </label>
                                        <select x-model="formData.day_of_week"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                                required>
                                            <option value="">Выберите день</option>
                                            <option value="1">Понедельник</option>
                                            <option value="2">Вторник</option>
                                            <option value="3">Среда</option>
                                            <option value="4">Четверг</option>
                                            <option value="5">Пятница</option>
                                            <option value="6">Суббота</option>
                                            <option value="7">Воскресенье</option>
                                        </select>
                                    </div>

                                    <!-- Время (только для добавления) -->
                                    <div x-show="!isEditing">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Время
                                        </label>
                                        <select x-model="formData.time_slot"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                                required>
                                            <option value="">Выберите время</option>
                                            <template x-for="(label, value) in timeSlots" :key="value">
                                                <option :value="value" x-text="label"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Неделя -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Неделя
                                        </label>
                                        <select x-model="formData.week_number"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                                required>
                                            <option value="">Выберите неделю</option>
                                            <template x-for="week in weeks" :key="week.number">
                                                <option :value="week.number" x-text="week.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit"
                                        :disabled="loading"
                                        class="modal-button-save w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 dark:bg-blue-500 text-base font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                        style="background-color: #2563eb !important; color: #ffffff !important; border-color: #2563eb !important;">
                            <span x-show="!loading" x-text="isEditing ? 'Сохранить' : 'Добавить'"></span>
                            <span x-show="loading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Сохранение...
                            </span>
                        </button>
                                <button type="button"
                                        @click="closeModal()"
                                        class="modal-button-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                        style="background-color: #ffffff !important; color: #374151 !important; border-color: #d1d5db !important;">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <script>
    function scheduleManagementApp() {
        return {
            selectedCourse: '',
            selectedGroup: '',
            selectedWeek: '',
            courses: [],
            groups: [],
            weeks: [],
            subjects: [],
            teachers: [],
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
            
            // Модальное окно
            showModal: false,
            isEditing: false,
            loading: false,
            formData: {
                id: null,
                subject_id: '',
                teacher_id: '',
                classroom: '',
                day_of_week: '',
                time_slot: '',
                week_number: ''
            },

            async init() {
                await this.loadCourses();
                await this.loadWeeks();
                await this.loadSubjects();
                await this.loadTeachers();
            },

            async loadCourses() {
                try {
                    const response = await fetch('/api/courses');
                    this.courses = await response.json();
                } catch (error) {
                    console.error('Ошибка загрузки курсов:', error);
                    this.courses = [];
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
                    console.error('Ошибка загрузки групп:', error);
                    this.groups = [];
                } finally {
                    this.loadingGroups = false;
                }
            },

            async onGroupChange() {
                this.scheduleData = null;
                this.selectedWeek = '';
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
                    console.error('Ошибка загрузки недель:', error);
                    this.weeks = [];
                } finally {
                    this.loadingWeeks = false;
                }
            },

            async loadSubjects() {
                try {
                    const response = await fetch('/api/subjects');
                    this.subjects = await response.json();
                } catch (error) {
                    console.error('Ошибка загрузки предметов:', error);
                    this.subjects = [];
                }
            },

            async loadTeachers() {
                try {
                    const response = await fetch('/api/teachers');
                    this.teachers = await response.json();
                } catch (error) {
                    console.error('Ошибка загрузки преподавателей:', error);
                    this.teachers = [];
                }
            },

            async loadSchedule(forceRefresh = false) {
                if (!this.selectedGroup || !this.selectedWeek) return;

                this.loadingSchedule = true;
                try {
                    // Добавляем timestamp для предотвращения кэширования
                    const timestamp = forceRefresh ? `?t=${Date.now()}` : '';
                    const url = `/api/groups/${this.selectedGroup}/schedule/${this.selectedWeek}${timestamp}`;
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    // Очищаем старые данные перед загрузкой новых
                    this.scheduleData = null;
                    
                    // Небольшая задержка для визуального обновления
                    await new Promise(resolve => setTimeout(resolve, 100));
                    
                    this.scheduleData = data.schedule;
                    // Не перезаписываем daysOfWeek и timeSlots, они уже инициализированы
                    this.subjectTypes = data.subject_types;
                } catch (error) {
                    console.error('Ошибка загрузки расписания:', error);
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
                    5: 'Пт', 6: 'Сб', 7: 'Вс'
                };
                return dayNames[day] || day;
            },

            getScheduleItem(day, timeSlot) {
                return this.scheduleData?.[day]?.[timeSlot] || null;
            },

            getSubjectClass(subjectType) {
                if (subjectType === 'lecture') {
                    return 'schedule-item-lecture';
                } else if (subjectType === 'practice') {
                    return 'schedule-item-practice';
                } else {
                    return 'schedule-item-default';
                }
            },

            openEditModal(id, subject, teacher, classroom, day, time, week) {
                this.isEditing = true;
                this.formData = {
                    id: id,
                    subject_id: this.subjects.find(s => s.name === subject)?.id || '',
                    teacher_id: this.teachers.find(t => t.name === teacher)?.id || '',
                    classroom: classroom || '',
                    day_of_week: day,
                    time_slot: time,
                    week_number: week || this.selectedWeek
                };
                this.showModal = true;
            },

            openAddModal(day, time) {
                this.isEditing = false;
                this.formData = {
                    id: null,
                    subject_id: '',
                    teacher_id: '',
                    classroom: '',
                    day_of_week: day,
                    time_slot: time,
                    week_number: this.selectedWeek
                };
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
                this.formData = {
                    id: null,
                    subject_id: '',
                    teacher_id: '',
                    classroom: '',
                    day_of_week: '',
                    time_slot: '',
                    week_number: ''
                };
            },

            async saveLesson() {
                this.loading = true;
                try {
                    const url = this.isEditing ? `/api/schedules/${this.formData.id}` : '/api/schedules';
                    const method = this.isEditing ? 'PUT' : 'POST';
                    
                    const requestData = {
                        ...this.formData,
                        group_id: this.selectedGroup
                    };
                    
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(requestData)
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        this.closeModal();
                        
                        // Показываем уведомление об успехе
                        this.showNotification('success', result.message);
                        
                        // Перезагружаем расписание
                        await this.loadSchedule(true);
                    } else {
                        // Показываем ошибку
                        this.showNotification('error', result.message || 'Неизвестная ошибка');
                    }
                } catch (error) {
                    console.error('Ошибка сохранения:', error);
                    this.showNotification('error', 'Ошибка сохранения занятия: ' + error.message);
                } finally {
                    this.loading = false;
                }
            },

            async updateScheduleData(newSchedule) {
                if (!newSchedule || !this.scheduleData) return;
                
                // Обновляем конкретный элемент в расписании
                const day = newSchedule.day_of_week;
                const timeSlot = newSchedule.time_slot;
                
                // Добавляем новое занятие
                if (!this.scheduleData[day]) {
                    this.scheduleData[day] = {};
                }
                this.scheduleData[day][timeSlot] = {
                    id: newSchedule.id,
                    subject: newSchedule.subject?.name || 'Неизвестный предмет',
                    subject_type: newSchedule.subject?.type || 'lecture',
                    teacher: newSchedule.teacher?.name || 'Неизвестный преподаватель',
                    classroom: newSchedule.classroom,
                    week_number: newSchedule.week_number
                };
                
                // Принудительно обновляем Alpine.js
                this.$nextTick(() => {
                    this.scheduleData = { ...this.scheduleData };
                });
            },

            showNotification(type, message) {
                // Создаем уведомление
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-xl max-w-sm border-2 ${
                    type === 'success' 
                        ? 'bg-green-100 dark:bg-green-800 border-green-300 dark:border-green-600 text-green-900 dark:text-green-100' 
                        : 'bg-red-100 dark:bg-red-800 border-red-300 dark:border-red-600 text-red-900 dark:text-red-100'
                }`;
                
                // Добавляем inline стили для принудительного перебивания Filament
                notification.style.cssText = `
                    background-color: ${type === 'success' ? '#dcfce7' : '#fecaca'} !important;
                    border-color: ${type === 'success' ? '#16a34a' : '#dc2626'} !important;
                    color: ${type === 'success' ? '#14532d' : '#991b1b'} !important;
                    opacity: 1 !important;
                    z-index: 9999 !important;
                `;
                
                // Для темной темы
                if (document.documentElement.classList.contains('dark')) {
                    notification.style.cssText = `
                        background-color: ${type === 'success' ? '#166534' : '#991b1b'} !important;
                        border-color: ${type === 'success' ? '#22c55e' : '#ef4444'} !important;
                        color: ${type === 'success' ? '#dcfce7' : '#fecaca'} !important;
                        opacity: 1 !important;
                        z-index: 9999 !important;
                    `;
                }
                
                // Принудительно обновляем стили кнопок в модальном окне
                setTimeout(() => {
                    const saveButton = document.querySelector('.modal-button-save');
                    const cancelButton = document.querySelector('.modal-button-cancel');
                    
                    if (saveButton) {
                        if (document.documentElement.classList.contains('dark')) {
                            saveButton.style.cssText = 'background-color: #3b82f6 !important; color: #ffffff !important; border-color: #3b82f6 !important;';
                        } else {
                            saveButton.style.cssText = 'background-color: #2563eb !important; color: #ffffff !important; border-color: #2563eb !important;';
                        }
                    }
                    
                    if (cancelButton) {
                        if (document.documentElement.classList.contains('dark')) {
                            cancelButton.style.cssText = 'background-color: #4b5563 !important; color: #e5e7eb !important; border-color: #6b7280 !important;';
                        } else {
                            cancelButton.style.cssText = 'background-color: #ffffff !important; color: #374151 !important; border-color: #d1d5db !important;';
                        }
                    }
                }, 100);
                
                notification.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            ${type === 'success' 
                                ? '<svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
                                : '<svg class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>'
                            }
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">${message}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="this.parentElement.parentElement.remove()" class="inline-flex text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Автоматически удаляем уведомление через 5 секунд
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);
            }
        }
    }
    </script>
</x-filament-panels::page>