<?php

use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Home: logged-in users go straight to the workspace, guests to the Breeze login page
Route::redirect('/', '/workspace');

// Agentic AI workspace (Livewire). Only for logged-in users; every query inside is scoped to auth()->id()
Route::get('/workspace', WorkspaceController::class)->middleware('auth')->name('workspace');

Route::middleware('auth')->group(function () {
    // Background scan of this app's source code (Challenge 3, wire:poll). Its own page: it is about the
    // project, not about a conversation in the workspace.
    Route::view('/scan', 'scan')->name('scan');

    // Browser playground for the Pertemuan 11 token API. Uses the same login as the workspace:
    // its Bearer token is issued from the session, so there is no second login form.
    Route::get('/playground', [PlaygroundController::class, 'show'])->name('playground');
    Route::post('/playground/token', [PlaygroundController::class, 'token'])
        ->middleware('throttle:10,1')
        ->name('playground.token');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
