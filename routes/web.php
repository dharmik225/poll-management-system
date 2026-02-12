<?php

use App\Http\Controllers\Auth\AdminRegisterController;
use App\Livewire\Polls\Index as PollsIndex;
use App\Livewire\Polls\Show as PollsShow;
use App\Livewire\PollVote;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::livewire('polls/{poll:slug}', PollVote::class)
    ->middleware('throttle:60,1')
    ->name('polls.vote');

// Admin auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('admin/login', fn () => view('livewire.auth.admin-login'))
        ->name('admin.login');
    Route::post('admin/login', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
    Route::get('admin/register', [AdminRegisterController::class, 'create'])
        ->name('admin.register');
    Route::post('admin/register', [AdminRegisterController::class, 'store'])
        ->name('admin.register.store');
});

// Admin routes (requires authentication + admin role)
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('polls', PollsIndex::class)->name('polls.index');
    Route::livewire('polls/{poll}', PollsShow::class)->name('polls.show');
});

// Authenticated user routes (non-admin)
Route::middleware(['auth', 'verified'])->group(function () {
    // Add user-specific authenticated routes here
});

require __DIR__.'/settings.php';
