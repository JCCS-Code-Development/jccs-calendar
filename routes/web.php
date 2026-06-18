<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\UserController;

Route::redirect('/dashboard', '/calendar')
    ->middleware(['auth']);

Route::get('/calendar', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

use App\Http\Controllers\EventController;

Route::redirect('/events', '/all-events')
    ->middleware(['auth']);

Route::resource('all-events', EventController::class)
    ->parameters(['all-events' => 'event'])
    ->names('events')
    ->middleware(['auth']);

Route::patch('/all-events/{event}/status', [EventController::class, 'updateStatus'])
    ->middleware(['auth'])
    ->name('events.update-status');

Route::patch('/all-events/{event}/mark-done', [EventController::class, 'markDone'])
    ->middleware(['auth'])
    ->name('events.mark-done');


Route::get('/my-events', [EventController::class, 'myEvents'])
    ->middleware(['auth'])
    ->name('events.mine');

Route::get('/to-do-list', [EventController::class, 'todos'])
    ->middleware(['auth'])
    ->name('events.todos');

Route::get('/calendar-view', [CalendarController::class, 'index'])
    ->middleware(['auth'])
    ->name('calendar.index');

Route::get('/calendar/events', [CalendarController::class, 'events'])
    ->middleware(['auth'])
    ->name('calendar.events');

Route::resource('users', UserController::class)
    ->middleware(['auth']);

require __DIR__.'/auth.php';