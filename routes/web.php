<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/my-tasks', [WorkspaceController::class, 'myTasks'])->name('tasks.mine');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::resource('projects', ProjectController::class);
    Route::resource('tasks', TaskController::class)->except('show');
    Route::get('/calendar', [WorkspaceController::class, 'calendar'])->name('calendar');
    Route::get('/reports', [WorkspaceController::class, 'reports'])->name('reports');
    Route::get('/members', [WorkspaceController::class, 'members'])->name('members');
    Route::get('/labels', [WorkspaceController::class, 'labels'])->name('labels');
    Route::get('/settings', [WorkspaceController::class, 'settings'])->name('settings');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
