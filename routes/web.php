<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // --- Admin Only Routes ---
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/settings', function () {
            return "Admin Settings Page";
        })->name('admin.settings');
        // Add User Management, Global Reports here
    });

    // --- Sales Rep Only Routes ---
    Route::middleware(['role:sales'])->group(function () {
        Route::get('/proposals/create', function () {
            return "Create Proposal Page";
        })->name('proposals.create');
        // Add Proposal creation, Client management here
    });
});

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });


Route::middleware(['auth', 'role:sales'])->group(function () {
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::get('/proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
    Route::patch('/proposals/{proposal}', [ProposalController::class, 'update'])->name('proposals.update');
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
});
require __DIR__.'/auth.php';