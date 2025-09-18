@props(['editable' => false])

<div class="overflow-x-auto">
    <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-600">
                    Час
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
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white" x-text="timeSlot"></td>
                    <template x-for="day in daysOfWeek" :key="day">
                        <td class="px-2 py-3 text-center">
                            <div x-show="getScheduleItem(day, timeSlot)" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="p-2 rounded-lg text-xs shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
                                 :class="getSubjectColor(getScheduleItem(day, timeSlot)?.subject_type)"
                                 :title="'Преподаватель: ' + getScheduleItem(day, timeSlot)?.teacher + (getScheduleItem(day, timeSlot)?.classroom ? '\\nАудитория: ' + getScheduleItem(day, timeSlot)?.classroom : '')"
                                 @if($editable)
                                 @click="openEditModal(getScheduleItem(day, timeSlot)?.id, getScheduleItem(day, timeSlot)?.subject, getScheduleItem(day, timeSlot)?.teacher, getScheduleItem(day, timeSlot)?.classroom || '', day, timeSlot, getScheduleItem(day, timeSlot)?.week_number)"
                                 @endif>
                                <div class="font-medium truncate" x-text="getScheduleItem(day, timeSlot)?.subject"></div>
                                <div class="text-gray-600 dark:text-gray-400 truncate" x-text="getScheduleItem(day, timeSlot)?.teacher"></div>
                                <div x-show="getScheduleItem(day, timeSlot)?.classroom" 
                                     class="text-gray-500 dark:text-gray-500 truncate" 
                                     x-text="'Ауд. ' + getScheduleItem(day, timeSlot)?.classroom"></div>
                                @if($editable)
                                <div class="text-xs opacity-50 mt-1">Клик для редактирования</div>
                                @endif
                            </div>
                            @if($editable)
                            <div x-show="!getScheduleItem(day, timeSlot)" 
                                 class="p-2 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 text-center cursor-pointer hover:border-blue-400 hover:text-blue-500 transition-colors"
                                 @click="openAddModal(day, timeSlot)">
                                <div class="text-xs">+ Добавить занятие</div>
                            </div>
                            @else
                            <div x-show="!getScheduleItem(day, timeSlot)" 
                                 class="p-2 text-gray-400 dark:text-gray-500 text-xs italic">
                                Свободно
                            </div>
                            @endif
                        </td>
                    </template>
                </tr>
            </template>
        </tbody>
    </table>
</div>

@if($editable)
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
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                <span x-text="isEditing ? 'Редактировать занятие' : 'Добавить занятие'"></span>
                            </h3>
                            
                            <div class="space-y-4">
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
                                        <option value="08:00-09:30">08:00-09:30</option>
                                        <option value="09:45-11:15">09:45-11:15</option>
                                        <option value="11:30-13:00">11:30-13:00</option>
                                        <option value="13:30-15:00">13:30-15:00</option>
                                        <option value="15:15-16:45">15:15-16:45</option>
                                        <option value="17:00-18:30">17:00-18:30</option>
                                        <option value="18:45-20:15">18:45-20:15</option>
                                    </select>
                                </div>

                                <!-- Неделя -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Неделя
                                    </label>
                                    <select x-model="formData.week_number" 
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="">Все недели</option>
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
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 dark:bg-blue-500 text-base font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-blue-400 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading" x-text="isEditing ? 'Сохранить' : 'Добавить'"></span>
                        <span x-show="loading" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Сохранение...
                        </span>
                    </button>
                    <button type="button" 
                            @click="closeModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
