<?php

use App\Http\Controllers\CampController;
use App\Http\Controllers\CampPaymentController;
use App\Http\Controllers\CampRoomController;
use App\Http\Controllers\CampTeamController;
use App\Http\Controllers\CampVerificationPointController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
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

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('can:manage-users');

    Route::get('users/{user}', [UserController::class, 'show'])
        ->name('users.show');

    Route::resource('camps', CampController::class)
        ->middleware('can:manage-camps');

    Route::get('camps/{camp}/payments', [CampPaymentController::class, 'index'])
        ->middleware('can:manage-payments')
        ->name('camps.payments.index');

    Route::post('camps/{camp}/payments', [CampPaymentController::class, 'store'])
        ->middleware('can:manage-payments')
        ->name('camps.payments.store');

    Route::patch('camps/{camp}/payments/{payment}', [CampPaymentController::class, 'update'])
        ->middleware('can:manage-payments')
        ->name('camps.payments.update');

    Route::delete('camps/{camp}/payments/{payment}', [CampPaymentController::class, 'destroy'])
        ->middleware('can:manage-payments')
        ->name('camps.payments.destroy');

    Route::get('camps/{camp}/rooms', [CampRoomController::class, 'index'])
        ->middleware('can:manage-camps')
        ->name('camps.rooms.index');

    Route::post('camps/{camp}/rooms', [CampRoomController::class, 'store'])
        ->middleware('can:manage-camps')
        ->name('camps.rooms.store');

    Route::patch('camps/{camp}/rooms/assignments', [CampRoomController::class, 'assign'])
        ->middleware('can:manage-camps')
        ->name('camps.rooms.assignments.update');

    Route::patch('camps/{camp}/rooms/{room}', [CampRoomController::class, 'update'])
        ->middleware('can:manage-camps')
        ->name('camps.rooms.update');

    Route::delete('camps/{camp}/rooms/{room}', [CampRoomController::class, 'destroy'])
        ->middleware('can:manage-camps')
        ->name('camps.rooms.destroy');

    Route::get('camps/{camp}/teams', [CampTeamController::class, 'index'])
        ->middleware('can:manage-camps')
        ->name('camps.teams.index');

    Route::post('camps/{camp}/teams', [CampTeamController::class, 'store'])
        ->middleware('can:manage-camps')
        ->name('camps.teams.store');

    Route::patch('camps/{camp}/teams/assignments', [CampTeamController::class, 'assign'])
        ->middleware('can:manage-camps')
        ->name('camps.teams.assignments.update');

    Route::patch('camps/{camp}/teams/{team}', [CampTeamController::class, 'update'])
        ->middleware('can:manage-camps')
        ->name('camps.teams.update');

    Route::delete('camps/{camp}/teams/{team}', [CampTeamController::class, 'destroy'])
        ->middleware('can:manage-camps')
        ->name('camps.teams.destroy');

    Route::get('camps/{camp}/verification-points', [CampVerificationPointController::class, 'index'])
        ->middleware('can:manage-operations')
        ->name('camps.verification-points.index');

    Route::post('camps/{camp}/verification-points', [CampVerificationPointController::class, 'store'])
        ->middleware('can:manage-operations')
        ->name('camps.verification-points.store');

    Route::get('camps/{camp}/verification-points/{verificationPoint}', [CampVerificationPointController::class, 'show'])
        ->middleware('can:manage-operations')
        ->name('camps.verification-points.show');

    Route::patch('camps/{camp}/verification-points/{verificationPoint}', [CampVerificationPointController::class, 'update'])
        ->middleware('can:manage-operations')
        ->name('camps.verification-points.update');

    Route::delete('camps/{camp}/verification-points/{verificationPoint}', [CampVerificationPointController::class, 'destroy'])
        ->middleware('can:manage-operations')
        ->name('camps.verification-points.destroy');

    Route::post('camps/{camp}/verification-points/{verificationPoint}/verifications', [CampVerificationPointController::class, 'verify'])
        ->middleware('can:manage-operations')
        ->name('camps.verification-points.verifications.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
