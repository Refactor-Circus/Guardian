<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\SecurityEvent\Http\Controllers\SecurityEventController;

Route::get('security-events', [SecurityEventController::class, 'index'])->name('security-events.index');
Route::get('security-events/{event}', [SecurityEventController::class, 'show'])->name('security-events.show');
Route::get('me/security-events', [SecurityEventController::class, 'mine'])->name('me.security-events.index');
