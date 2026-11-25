<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Role\Http\Controllers\RoleAssignmentController;
use RefactorCircus\Guardian\Domains\Role\Http\Controllers\RoleController;
use RefactorCircus\Guardian\Domains\Role\Http\Controllers\UserAbilityController;

Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

Route::get('role-assignments', [RoleAssignmentController::class, 'index'])->name('role-assignments.index');
Route::post('role-assignments', [RoleAssignmentController::class, 'store'])->name('role-assignments.store');
Route::delete('role-assignments/{assignment}', [RoleAssignmentController::class, 'destroy'])->name('role-assignments.destroy');

Route::get('users/{user}/abilities', [UserAbilityController::class, 'index'])->name('users.abilities.index');
