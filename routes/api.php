<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarExportController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

// Public VAPID key (needed by frontend before auth)
Route::get('/push/key', [PushController::class, 'publicKey']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Events
    Route::get('/events', [EventApiController::class, 'index']);
    Route::post('/events', [EventApiController::class, 'store']);
    Route::get('/events/{event}', [EventApiController::class, 'show']);
    Route::put('/events/{event}', [EventApiController::class, 'update']);
    Route::delete('/events/{event}', [EventApiController::class, 'destroy']);
    Route::patch('/events/{event}/mark-done', [EventApiController::class, 'markDone']);

    Route::get('/my-events', [EventApiController::class, 'myEvents']);
    Route::get('/todos', [EventApiController::class, 'todos']);
    Route::get('/calendar-events', [EventApiController::class, 'calendarEvents']);
    Route::get('/event-types', [EventApiController::class, 'eventTypes']);

    // Users
    Route::get('/users', [UserApiController::class, 'index']);
    Route::post('/users', [UserApiController::class, 'store']);
    Route::get('/users/{user}', [UserApiController::class, 'show']);
    Route::put('/users/{user}', [UserApiController::class, 'update']);
    Route::delete('/users/{user}', [UserApiController::class, 'destroy']);

    // Roles
    Route::get('/roles', [RoleApiController::class, 'index']);

    // Push notifications
    Route::post('/push/subscribe', [PushController::class, 'subscribe']);
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe']);

    // Calendar export (iCal feed + single event)
    Route::get('/calendar.ics', [CalendarExportController::class, 'feed']);
    Route::get('/events/{event}/export.ics', [CalendarExportController::class, 'single']);
});
