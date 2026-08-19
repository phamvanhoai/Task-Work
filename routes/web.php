<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTrackingExportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ZaloBotWebhookController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::post('/webhooks/zalo-bot', ZaloBotWebhookController::class)->name('webhooks.zalo-bot');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/my-tasks', [WorkspaceController::class, 'myTasks'])->name('tasks.mine');
    Route::get('/tasks/export/project-tracking', ProjectTrackingExportController::class)->name('tasks.export.project-tracking');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::resource('projects', ProjectController::class);
    Route::resource('tasks', TaskController::class)->except('show');
    Route::get('/calendar', [WorkspaceController::class, 'calendar'])->name('calendar');
    Route::get('/reports', [WorkspaceController::class, 'reports'])->name('reports');
    Route::get('/members', [WorkspaceController::class, 'members'])->name('members');
    Route::post('/members', [WorkspaceController::class, 'inviteMember'])->name('members.store');
    Route::put('/members/{member}', [WorkspaceController::class, 'updateMember'])->name('members.update');
    Route::delete('/members/{member}', [WorkspaceController::class, 'destroyMember'])->name('members.destroy');
    Route::get('/labels', [WorkspaceController::class, 'labels'])->name('labels');
    Route::post('/labels', [WorkspaceController::class, 'storeLabel'])->name('labels.store');
    Route::put('/labels/{label}', [WorkspaceController::class, 'updateLabel'])->name('labels.update');
    Route::delete('/labels/{label}', [WorkspaceController::class, 'destroyLabel'])->name('labels.destroy');
    Route::get('/settings', [WorkspaceController::class, 'settings'])->name('settings');
    Route::put('/settings/profile', [WorkspaceController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/preferences', [WorkspaceController::class, 'updatePreferences'])->name('settings.preferences');
    Route::put('/settings/password', [WorkspaceController::class, 'updatePassword'])->name('settings.password');
    Route::delete('/settings/sessions', [WorkspaceController::class, 'destroyOtherSessions'])->name('settings.sessions.destroy');
    Route::get('/settings/export', [WorkspaceController::class, 'exportSettings'])->name('settings.export');
    Route::put('/settings/zalo/group', [WorkspaceController::class, 'updateZaloGroup'])->name('settings.zalo.group');
    Route::delete('/settings/zalo/link', [WorkspaceController::class, 'unlinkZalo'])->name('settings.zalo.unlink');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
