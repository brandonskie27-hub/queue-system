<?php

use App\Http\Controllers\DisplayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\CounterSelectionController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Middleware\EnsureCounterSelected;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Student side: no login, identified by their browser session.
Route::get('/queue', [TicketController::class, 'index'])->name('tickets.index');
Route::post('/queue/{service}/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

// Public "Now Serving" board for the waiting-area screen.
Route::get('/display', DisplayController::class)->name('display');

// Staff side: log in, pick a counter for this session, then work the queue.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/counter', [CounterSelectionController::class, 'edit'])->name('counter.edit');
    Route::put('/counter', [CounterSelectionController::class, 'update'])->name('counter.update');

    Route::middleware(EnsureCounterSelected::class)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/call-next', [DashboardController::class, 'callNext'])->name('dashboard.call-next');
        Route::post('/dashboard/tickets/{ticket}/done', [DashboardController::class, 'done'])->name('dashboard.tickets.done');
        Route::post('/dashboard/tickets/{ticket}/skip', [DashboardController::class, 'skip'])->name('dashboard.tickets.skip');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
