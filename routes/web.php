<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\Settings\PipelineController;
use App\Http\Controllers\Settings\StageController;
use App\Http\Controllers\Settings\SubStageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', fn () => redirect()->route('dashboard'));

    // Leads
    Route::post('leads/{lead}/move', [LeadController::class, 'move'])->name('leads.move');
    Route::resource('leads', LeadController::class);

    Route::middleware('role:super_admin,admin')
        ->prefix('settings')
        ->name('settings.')
        ->group(function () {

            // Pipelines
            Route::get('pipelines', [PipelineController::class, 'index'])->name('pipelines.index');
            Route::get('pipelines/create', [PipelineController::class, 'create'])->name('pipelines.create');
            Route::post('pipelines', [PipelineController::class, 'store'])->name('pipelines.store');
            Route::post('pipelines/sort', [PipelineController::class, 'sort'])->name('pipelines.sort');
            Route::get('pipelines/{pipeline}/edit', [PipelineController::class, 'edit'])->name('pipelines.edit');
            Route::put('pipelines/{pipeline}', [PipelineController::class, 'update'])->name('pipelines.update');
            Route::delete('pipelines/{pipeline}', [PipelineController::class, 'destroy'])->name('pipelines.destroy');

            // Stages (nested under pipeline)
            Route::post('pipelines/{pipeline}/stages/sort', [StageController::class, 'sort'])->name('stages.sort');
            Route::post('pipelines/{pipeline}/stages', [StageController::class, 'store'])->name('stages.store');
            Route::put('pipelines/{pipeline}/stages/{stage}', [StageController::class, 'update'])->name('stages.update');
            Route::delete('pipelines/{pipeline}/stages/{stage}', [StageController::class, 'destroy'])->name('stages.destroy');

            // Sub-stages (nested under stage)
            Route::post('stages/{stage}/sub-stages/sort', [SubStageController::class, 'sort'])->name('sub-stages.sort');
            Route::post('stages/{stage}/sub-stages', [SubStageController::class, 'store'])->name('sub-stages.store');
            Route::put('stages/{stage}/sub-stages/{subStage}', [SubStageController::class, 'update'])->name('sub-stages.update');
            Route::delete('stages/{stage}/sub-stages/{subStage}', [SubStageController::class, 'destroy'])->name('sub-stages.destroy');
        });
});
