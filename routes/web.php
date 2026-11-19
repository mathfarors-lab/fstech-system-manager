<?php

use App\Http\Controllers\Admin\BuildController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OperationLogController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TaskNoteController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VersionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Auth routes
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.edit');
    Route::patch('/profile', [AuthController::class, 'profileUpdate'])->name('profile.update');
    Route::put('/password', [AuthController::class, 'passwordUpdate'])->name('password.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Read-only for all authenticated users
    Route::get('systems', [SystemController::class, 'index'])->name('systems.index');
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('logs', [OperationLogController::class, 'index'])->name('logs.index');
    Route::get('finance', [FinanceController::class, 'dashboard'])->name('finance.dashboard');
    Route::get('finance/transactions', [FinanceController::class, 'index'])->name('finance.transactions');

    // Write access: admin + developer (registered before {system} show route)
    Route::middleware('role:admin,developer')->group(function () {
        Route::get('systems/create', [SystemController::class, 'create'])->name('systems.create');
        Route::post('systems', [SystemController::class, 'store'])->name('systems.store');
        Route::get('systems/{system}/edit', [SystemController::class, 'edit'])->name('systems.edit');
        Route::get('systems/{system}/invoice', [SystemController::class, 'invoice'])->name('systems.invoice');
        Route::get('systems/{system}/invoice/pdf', [SystemController::class, 'invoicePdf'])->name('systems.invoice.pdf');
        Route::put('systems/{system}', [SystemController::class, 'update'])->name('systems.update');
        Route::delete('systems/{system}', [SystemController::class, 'destroy'])->name('systems.destroy');
        Route::post('systems/{id}/restore', [SystemController::class, 'restore'])->name('systems.restore');

        Route::post('systems/{system}/versions', [VersionController::class, 'store'])->name('systems.versions.store');
        Route::put('systems/{system}/versions/{version}', [VersionController::class, 'update'])->name('systems.versions.update');
        Route::delete('systems/{system}/versions/{version}', [VersionController::class, 'destroy'])->name('systems.versions.destroy');

        Route::post('systems/{system}/builds', [BuildController::class, 'store'])->name('systems.builds.store');
        Route::put('systems/{system}/builds/{build}', [BuildController::class, 'update'])->name('systems.builds.update');
        Route::delete('systems/{system}/builds/{build}', [BuildController::class, 'destroy'])->name('systems.builds.destroy');

        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        Route::post('tasks/{task}/notes', [TaskNoteController::class, 'store'])->name('tasks.notes.store');
        Route::patch('notes/{note}/toggle', [TaskNoteController::class, 'toggle'])->name('notes.toggle');
        Route::patch('notes/{note}', [TaskNoteController::class, 'update'])->name('notes.update');
        Route::delete('notes/{note}', [TaskNoteController::class, 'destroy'])->name('notes.destroy');

        Route::post('systems/{system}/logs', [OperationLogController::class, 'store'])->name('systems.logs.store');
        Route::delete('systems/{system}/logs/{log}', [OperationLogController::class, 'destroy'])->name('systems.logs.destroy');

        Route::post('systems/{system}/milestones/{milestone}/collect', [SystemController::class, 'collectMilestonePayment'])->name('systems.milestones.collect');
        Route::post('systems/{system}/milestones/{milestone}/undo', [SystemController::class, 'undoMilestonePayment'])->name('systems.milestones.undo');

        Route::post('systems/{system}/maintenance/collect', [SystemController::class, 'collectMaintenancePayment'])->name('systems.maintenance.collect');
        Route::post('systems/{system}/maintenance/{payment}/undo', [SystemController::class, 'undoMaintenancePayment'])->name('systems.maintenance.undo');

        Route::post('finance/transactions', [FinanceController::class, 'store'])->name('finance.transactions.store');
        Route::put('finance/transactions/{transaction}', [FinanceController::class, 'update'])->name('finance.transactions.update');
        Route::delete('finance/transactions/{transaction}', [FinanceController::class, 'destroy'])->name('finance.transactions.destroy');
    });

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::delete('systems/{id}/force-delete', [SystemController::class, 'forceDelete'])->name('systems.force-delete');

        Route::resource('users', UserController::class)->except('show');
    });

    // System detail (slug-based) — last so /systems/create etc. resolve first
    Route::get('systems/{system}', [SystemController::class, 'show'])->name('systems.show');
});
