<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadProgramController;
use App\Http\Controllers\LeadTagController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\MetaPageController;
use App\Http\Controllers\Settings\PipelineController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\ProgramController;
use App\Http\Controllers\Settings\StageController;
use App\Http\Controllers\Settings\SubStageController;
use App\Http\Controllers\Settings\TagController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Meta webhook routes are registered in bootstrap/app.php with no middleware

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

    // Notes (nested under lead)
    Route::post('leads/{lead}/notes', [NoteController::class, 'store'])->name('leads.notes.store');
    Route::delete('leads/{lead}/notes/{note}', [NoteController::class, 'destroy'])->name('leads.notes.destroy');

    // Tasks (nested under lead)
    Route::post('leads/{lead}/tasks', [TaskController::class, 'store'])->name('leads.tasks.store');
    Route::patch('leads/{lead}/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('leads.tasks.toggle');
    Route::delete('leads/{lead}/tasks/{task}', [TaskController::class, 'destroy'])->name('leads.tasks.destroy');

    // Tags (nested under lead)
    Route::post('leads/{lead}/tags/{tag}/toggle', [LeadTagController::class, 'toggle'])->name('leads.tags.toggle');

    // Lead assignment actions
    Route::patch('leads/{lead}/assign-user', [LeadController::class, 'assignUser'])->name('leads.assign-user');
    Route::patch('leads/{lead}/assign-company', [LeadController::class, 'assignCompany'])->name('leads.assign-company');

    // Programs (nested under lead)
    Route::post('leads/{lead}/programs', [LeadProgramController::class, 'store'])->name('leads.programs.store');
    Route::post('leads/{lead}/programs/{leadProgram}/primary', [LeadProgramController::class, 'setPrimary'])->name('leads.programs.primary');
    Route::delete('leads/{lead}/programs/{leadProgram}', [LeadProgramController::class, 'destroy'])->name('leads.programs.destroy');

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

            // Tags
            Route::get('tags', [TagController::class, 'index'])->name('tags.index');
            Route::get('tags/create', [TagController::class, 'create'])->name('tags.create');
            Route::post('tags', [TagController::class, 'store'])->name('tags.store');
            Route::get('tags/{tag}/edit', [TagController::class, 'edit'])->name('tags.edit');
            Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
            Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

            // Programs
            Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
            Route::get('programs/create', [ProgramController::class, 'create'])->name('programs.create');
            Route::post('programs', [ProgramController::class, 'store'])->name('programs.store');
            Route::get('programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
            Route::put('programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
            Route::delete('programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');

            // Companies
            Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
            Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
            Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
            Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
            Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
            Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');

            // Users
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // Meta Integration
            Route::get('meta', [MetaPageController::class, 'index'])->name('meta.index');
            Route::post('meta/pages', [MetaPageController::class, 'store'])->name('meta.pages.store');
            Route::put('meta/pages/{metaPage}', [MetaPageController::class, 'update'])->name('meta.pages.update');
            Route::delete('meta/pages/{metaPage}', [MetaPageController::class, 'destroy'])->name('meta.pages.destroy');
            Route::post('meta/pages/{metaPage}/mappings', [MetaPageController::class, 'storeMapping'])->name('meta.mappings.store');
            Route::delete('meta/pages/{metaPage}/mappings/{mapping}', [MetaPageController::class, 'destroyMapping'])->name('meta.mappings.destroy');
        });
});
