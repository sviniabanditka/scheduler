<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ScheduleController;

Route::get('/', [ScheduleController::class, 'index'])->name('home');

// API маршруты для расписания
Route::get('/api/courses/{courseId}/groups', [ScheduleController::class, 'getCourseGroups'])->name('api.course.groups');
Route::get('/api/groups/{groupId}/schedule/{week}', [ScheduleController::class, 'getSchedule'])->name('api.group.schedule');
Route::get('/api/weeks', [ScheduleController::class, 'getWeeks'])->name('api.weeks');

// API маршруты для админки
Route::get('/api/courses', [ScheduleController::class, 'getCourses'])->name('api.courses');
Route::get('/api/subjects', [ScheduleController::class, 'getSubjects'])->name('api.subjects');
Route::get('/api/teachers', [ScheduleController::class, 'getTeachers'])->name('api.teachers');
Route::post('/api/schedules', [ScheduleController::class, 'storeSchedule'])->name('api.schedules.store');
Route::put('/api/schedules/{id}', [ScheduleController::class, 'updateSchedule'])->name('api.schedules.update');
Route::delete('/api/schedules/{id}', [ScheduleController::class, 'deleteSchedule'])->name('api.schedules.delete');
