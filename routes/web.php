<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicScheduleController;
use App\Http\Controllers\ScheduleController;

// Auth routes
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Home page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public schedule pages (no auth required)
Route::get('/s/{slug}', [PublicScheduleController::class, 'show'])->name('public.schedule');
Route::get('/s/{slug}/api/groups/{courseId}', [PublicScheduleController::class, 'getGroups'])->name('public.schedule.groups');
Route::get('/s/{slug}/api/schedule/{groupId}/{startDate}/{endDate}', [PublicScheduleController::class, 'getScheduleData'])->name('public.schedule.data');

// API endpoints for schedule data (used by authenticated views)
Route::get('/api/courses/{courseId}/groups', [ScheduleController::class, 'getCourseGroups'])->name('api.course.groups');
Route::get('/api/groups/{groupId}/schedule/{startDate}/{endDate}', [ScheduleController::class, 'getSchedule'])->name('api.group.schedule');
Route::get('/api/weeks', [ScheduleController::class, 'getWeeks'])->name('api.weeks');
Route::get('/api/current-week', [ScheduleController::class, 'getCurrentWeekRange'])->name('api.current.week');
Route::get('/api/courses', [ScheduleController::class, 'getCourses'])->name('api.courses');
Route::get('/api/subjects', [ScheduleController::class, 'getSubjects'])->name('api.subjects');
Route::get('/api/teachers', [ScheduleController::class, 'getTeachers'])->name('api.teachers');
