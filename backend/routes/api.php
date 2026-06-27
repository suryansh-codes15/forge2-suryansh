<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Authenticated routes (Sanctum token required + organization context)
Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureOrganizationContext::class])->group(function () {

    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Tickets CRUD
    Route::get('/tickets',             [TicketController::class, 'index']);
    Route::post('/tickets',            [TicketController::class, 'store']);
    Route::get('/tickets/{ticket}',    [TicketController::class, 'show']);
    Route::patch('/tickets/{ticket}',  [TicketController::class, 'update']);
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy']);

    // Comments (threaded replies + internal notes)
    Route::get('/tickets/{ticket}/comments',  [CommentController::class, 'index']);
    Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store']);

    // Dashboard
    Route::get('/dashboard/stats',     [DashboardController::class, 'stats']);
    Route::get('/dashboard/agents',    [DashboardController::class, 'agents']);
    Route::get('/dashboard/customers', [DashboardController::class, 'customers']);

    // Notifications
    Route::get('/notifications',                       [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read',  [App\Http\Controllers\Api\NotificationController::class, 'read']);
    Route::post('/notifications/read-all',             [App\Http\Controllers\Api\NotificationController::class, 'readAll']);
});
