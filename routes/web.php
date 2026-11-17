<?php

use App\Http\Controllers\TicketCategoryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::resource('ticket-categories', TicketCategoryController::class)
        ->only(['index', 'store', 'update']);

    Route::patch('ticket-categories/{ticketCategory}/deactivate', [TicketCategoryController::class, 'deactivate'])
        ->name('ticket-categories.deactivate');
});

require __DIR__ . '/settings.php';
