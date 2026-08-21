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
use App\Http\Controllers\Settings\CustomFieldController;
use App\Http\Controllers\Settings\TagController;
use App\Http\Controllers\Settings\TagGroupController;
use App\Http\Controllers\Settings\WaTemplateController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardCardController;
use App\Http\Controllers\CardNoteController;
use App\Http\Controllers\CardTaskController;
use App\Http\Controllers\UnifiedTaskController;
use App\Http\Controllers\TodoListController;
use App\Http\Controllers\TodoListItemController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\MetaAdsController;
use App\Http\Controllers\MetaBillingController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FinanceDocumentController;
use App\Http\Controllers\FinanceAccountController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\AccountTransferController;
use Illuminate\Support\Facades\Route;

// Meta webhook routes are registered in bootstrap/app.php with no middleware

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::get('/tasks', [UnifiedTaskController::class, 'index'])->name('tasks.index');

    // ToDo Lists
    Route::resource('todo-lists', TodoListController::class)->except(['edit']);
    Route::post('todo-lists/{todoList}/items', [TodoListItemController::class, 'store'])->name('todo-lists.items.store');
    Route::put('todo-lists/{todoList}/items/{item}', [TodoListItemController::class, 'update'])->name('todo-lists.items.update');
    Route::post('todo-lists/{todoList}/items/{item}/toggle', [TodoListItemController::class, 'toggle'])->name('todo-lists.items.toggle');
    Route::delete('todo-lists/{todoList}/items/{item}', [TodoListItemController::class, 'destroy'])->name('todo-lists.items.destroy');

    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('performance', [ReportsController::class, 'performance'])->name('performance.index');
    Route::get('reports/daily', [DailyReportController::class, 'index'])->name('reports.daily');
    Route::get('reports/meta-ads', [MetaAdsController::class, 'index'])->name('reports.meta-ads');
    Route::post('reports/meta-ads/sync', [MetaAdsController::class, 'sync'])->name('reports.meta-ads.sync');
    Route::post('reports/meta-ads/sync-day', [MetaAdsController::class, 'syncDay'])->name('reports.meta-ads.sync-day');
    Route::get('reports/meta-ads/missing-days', [MetaAdsController::class, 'missingDays'])->name('reports.meta-ads.missing-days');
    Route::get('reports/meta-ads/preview/{adId}', [MetaAdsController::class, 'preview'])->name('reports.meta-ads.preview');

    // Finance
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('finance/accounts', [FinanceAccountController::class, 'index'])->name('finance.accounts.index');
    Route::get('finance/categories', [FinanceAccountController::class, 'categoriesIndex'])->name('finance.categories.index');
    Route::post('finance/accounts', [FinanceAccountController::class, 'store'])->name('finance.accounts.store');
    Route::put('finance/accounts/{account}', [FinanceAccountController::class, 'update'])->name('finance.accounts.update');
    Route::delete('finance/accounts/{account}', [FinanceAccountController::class, 'destroy'])->name('finance.accounts.destroy');
    Route::get('finance/accounts/{account}', [FinanceAccountController::class, 'show'])->name('finance.accounts.show');
    Route::post('finance/groups', [FinanceAccountController::class, 'storeGroup'])->name('finance.groups.store');
    Route::delete('finance/groups/{group}', [FinanceAccountController::class, 'destroyGroup'])->name('finance.groups.destroy');
    Route::post('finance/categories', [FinanceAccountController::class, 'storeCategory'])->name('finance.categories.store');
    Route::delete('finance/categories/{category}', [FinanceAccountController::class, 'destroyCategory'])->name('finance.categories.destroy');
    Route::get('finance/my-account', [FinanceController::class, 'myAccount'])->name('finance.my-account');
    Route::get('finance/meta-billing', [MetaBillingController::class, 'index'])->name('finance.meta-billing');
    Route::post('finance/meta-billing/import', [MetaBillingController::class, 'import'])->name('finance.meta-billing.import');
    Route::get('finance/document/{type}/{id}', [FinanceDocumentController::class, 'show'])->name('finance.document');
    Route::get('finance/expenses', [ExpenseController::class, 'index'])->name('finance.expenses.index');
    Route::post('finance/expenses', [ExpenseController::class, 'store'])->name('finance.expenses.store');
    Route::post('finance/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('finance.expenses.approve');
    Route::post('finance/expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('finance.expenses.reject');
    Route::delete('finance/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('finance.expenses.destroy');
    Route::get('finance/incomes', [IncomeController::class, 'index'])->name('finance.incomes.index');
    Route::post('finance/incomes', [IncomeController::class, 'store'])->name('finance.incomes.store');
    Route::delete('finance/incomes/{income}', [IncomeController::class, 'destroy'])->name('finance.incomes.destroy');
    Route::get('finance/transfers', [AccountTransferController::class, 'index'])->name('finance.transfers.index');
    Route::post('finance/transfers', [AccountTransferController::class, 'store'])->name('finance.transfers.store');
    Route::delete('finance/transfers/{transfer}', [AccountTransferController::class, 'destroy'])->name('finance.transfers.destroy');

    // Payments
    Route::get('payments', [PaymentsController::class, 'index'])->name('payments.index');
    Route::post('payments', [PaymentsController::class, 'store'])->name('payments.store');
    Route::delete('payments/{payment}', [PaymentsController::class, 'destroy'])->name('payments.destroy');

    Route::get('/', fn () => redirect()->route('dashboard'));

    // Boards
    Route::resource('boards', BoardController::class);
    Route::post('boards/{board}/mark-read', [BoardController::class, 'markRead'])->name('boards.mark-read');
    Route::post('boards/{board}/cards', [BoardCardController::class, 'store'])->name('boards.cards.store');
    Route::put('boards/{board}/cards/{card}', [BoardCardController::class, 'update'])->name('boards.cards.update');
    Route::delete('boards/{board}/cards/{card}', [BoardCardController::class, 'destroy'])->name('boards.cards.destroy');
    Route::post('boards/{board}/cards/{card}/notes', [CardNoteController::class, 'store'])->name('boards.cards.notes.store');
    Route::delete('boards/{board}/cards/{card}/notes/{note}', [CardNoteController::class, 'destroy'])->name('boards.cards.notes.destroy');
    Route::post('boards/{board}/cards/{card}/tasks', [CardTaskController::class, 'store'])->name('boards.cards.tasks.store');
    Route::post('boards/{board}/cards/{card}/tasks/{task}/toggle', [CardTaskController::class, 'toggle'])->name('boards.cards.tasks.toggle');
    Route::delete('boards/{board}/cards/{card}/tasks/{task}', [CardTaskController::class, 'destroy'])->name('boards.cards.tasks.destroy');

    // Leads
    Route::post('leads/{lead}/move', [LeadController::class, 'move'])->name('leads.move');
    Route::get('kanban-cards/{stage}', [LeadController::class, 'kanbanCards'])->name('leads.kanban-cards');
    Route::resource('leads', LeadController::class);

    // Notes (nested under lead)
    Route::post('leads/{lead}/notes', [NoteController::class, 'store'])->name('leads.notes.store');
    Route::delete('leads/{lead}/notes/{note}', [NoteController::class, 'destroy'])->name('leads.notes.destroy');

    // Tasks (nested under lead)
    Route::post('leads/{lead}/tasks', [TaskController::class, 'store'])->name('leads.tasks.store');
    Route::post('leads/{lead}/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('leads.tasks.toggle');
    Route::delete('leads/{lead}/tasks/{task}', [TaskController::class, 'destroy'])->name('leads.tasks.destroy');

    // WhatsApp
    Route::post('leads/{lead}/whatsapp/send', [WhatsAppController::class, 'send'])->name('leads.whatsapp.send');
    Route::post('leads/{lead}/whatsapp/send-template', [WhatsAppController::class, 'sendTemplate'])->name('leads.whatsapp.send-template');

    // Tags (nested under lead)
    Route::post('leads/{lead}/tags/{tag}/toggle', [LeadTagController::class, 'toggle'])->name('leads.tags.toggle');
    Route::put('leads/{lead}/tags', [LeadTagController::class, 'sync'])->name('leads.tags.sync');

    // Lead assignment actions
    Route::patch('leads/{lead}/assign-user', [LeadController::class, 'assignUser'])->name('leads.assign-user');
    Route::patch('leads/{lead}/assign-company', [LeadController::class, 'assignCompany'])->name('leads.assign-company');

    // Lead custom field values
    Route::patch('leads/{lead}/custom-values', [LeadController::class, 'updateCustomValues'])->name('leads.custom-values.update');

    // Programs (nested under lead)
    Route::post('leads/{lead}/programs', [LeadProgramController::class, 'store'])->name('leads.programs.store');
    Route::post('leads/{lead}/programs/{leadProgram}/primary', [LeadProgramController::class, 'setPrimary'])->name('leads.programs.primary');
    Route::delete('leads/{lead}/programs/{leadProgram}', [LeadProgramController::class, 'destroy'])->name('leads.programs.destroy');

    Route::middleware('role:super_admin,admin')
        ->prefix('settings')
        ->name('settings.')
        ->group(function () {

            Route::get('permissions', fn () => view('settings.permissions'))->name('permissions');

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

            // Tag Groups
            Route::post('tag-groups', [TagGroupController::class, 'store'])->name('tag-groups.store');
            Route::put('tag-groups/{tagGroup}', [TagGroupController::class, 'update'])->name('tag-groups.update');
            Route::delete('tag-groups/{tagGroup}', [TagGroupController::class, 'destroy'])->name('tag-groups.destroy');

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

            // Custom Fields
            Route::get('custom-fields', [CustomFieldController::class, 'index'])->name('custom-fields.index');
            Route::post('custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
            Route::put('custom-fields/{customField}', [CustomFieldController::class, 'update'])->name('custom-fields.update');
            Route::delete('custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');
            Route::post('custom-fields/{customField}/options', [CustomFieldController::class, 'storeOption'])->name('custom-fields.options.store');
            Route::put('custom-fields/{customField}/options/{option}', [CustomFieldController::class, 'updateOption'])->name('custom-fields.options.update');
            Route::delete('custom-fields/{customField}/options/{option}', [CustomFieldController::class, 'destroyOption'])->name('custom-fields.options.destroy');
            Route::post('custom-fields/{customField}/mappings', [CustomFieldController::class, 'storeMapping'])->name('custom-fields.mappings.store');
            Route::delete('custom-fields/mappings/{mapping}', [CustomFieldController::class, 'destroyMapping'])->name('custom-fields.mappings.destroy');

            // WhatsApp Templates
            Route::get('wa-templates', [WaTemplateController::class, 'index'])->name('wa-templates.index');
            Route::post('wa-templates/sync', [WaTemplateController::class, 'sync'])->name('wa-templates.sync');
            Route::put('wa-templates/{waTemplate}', [WaTemplateController::class, 'update'])->name('wa-templates.update');

            // Meta Integration
            Route::get('meta', [MetaPageController::class, 'index'])->name('meta.index');
            Route::post('meta/pages', [MetaPageController::class, 'store'])->name('meta.pages.store');
            Route::put('meta/pages/{metaPage}', [MetaPageController::class, 'update'])->name('meta.pages.update');
            Route::delete('meta/pages/{metaPage}', [MetaPageController::class, 'destroy'])->name('meta.pages.destroy');
            Route::post('meta/pages/{metaPage}/subscribe', [MetaPageController::class, 'subscribe'])->name('meta.pages.subscribe');
            Route::post('meta/pages/{metaPage}/mappings', [MetaPageController::class, 'storeMapping'])->name('meta.mappings.store');
            Route::delete('meta/pages/{metaPage}/mappings/{mapping}', [MetaPageController::class, 'destroyMapping'])->name('meta.mappings.destroy');
        });
});
