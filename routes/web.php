<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Home: logged-in users go straight to the workspace, guests to the Breeze login page
Route::redirect('/', '/workspace');

// Agentic AI workspace (Livewire). Only for logged-in users; every query inside is scoped to auth()->id()
Route::get('/workspace', WorkspaceController::class)->middleware('auth')->name('workspace');

// Browser playground for the Pertemuan 11 token API (login, prompt, streaming, history)
Route::view('/playground', 'playground', ['themes' => array_keys(config('llm.themes'))])->name('playground');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
