<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BugReportController;
use App\Http\Controllers\BugReportReviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeatureRequestController;
use App\Http\Controllers\FeatureRequestReviewController;
use App\Http\Controllers\ItemClaimController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemTransitionController;
use App\Http\Controllers\ItemTriageController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\NeedApprovalController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TestCaseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('approval', [QueueController::class, 'approval'])->name('queues.approval');
    Route::post('approval/approve-all', [QueueController::class, 'approveAll'])->name('queues.approval.approve-all');
    Route::get('qa', [QueueController::class, 'qa'])->name('queues.qa');
    Route::get('items/available', [QueueController::class, 'available'])->name('queues.available');

    Route::get('projects/timeline', [ProjectController::class, 'timeline'])->name('projects.timeline');
    Route::resource('projects', ProjectController::class)->except('destroy');
    Route::post('projects/{project}/notes', [NoteController::class, 'storeForProject'])->name('projects.notes.store');

    Route::resource('items', ItemController::class);
    Route::post('items/{item}/transitions', ItemTransitionController::class)->name('items.transitions.store');
    Route::put('items/{item}/triage', ItemTriageController::class)->name('items.triage');
    Route::post('items/{item}/claim', ItemClaimController::class)->name('items.claim');
    Route::post('items/{item}/notes', [NoteController::class, 'storeForItem'])->name('items.notes.store');
    Route::post('items/{item}/test-cases', [TestCaseController::class, 'store'])->name('items.test-cases.store');

    Route::get('test-cases', [TestCaseController::class, 'index'])->name('test-cases.index');
    Route::put('test-cases/{testCase}', [TestCaseController::class, 'update'])->name('test-cases.update');
    Route::put('test-cases/{testCase}/result', [TestCaseController::class, 'result'])->name('test-cases.result');
    Route::delete('test-cases/{testCase}', [TestCaseController::class, 'destroy'])->name('test-cases.destroy');

    Route::put('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::get('feature-requests', [FeatureRequestController::class, 'index'])->name('feature-requests.index');
    Route::get('feature-requests/create', [FeatureRequestController::class, 'create'])->name('feature-requests.create');
    Route::post('feature-requests', [FeatureRequestController::class, 'store'])->name('feature-requests.store');
    Route::post('feature-requests/{featureRequest}/approve', [FeatureRequestReviewController::class, 'approve'])->name('feature-requests.approve');
    Route::post('feature-requests/{featureRequest}/escalate', [FeatureRequestReviewController::class, 'escalate'])->name('feature-requests.escalate');
    Route::post('feature-requests/{featureRequest}/reject', [FeatureRequestReviewController::class, 'reject'])->name('feature-requests.reject');

    Route::get('bug-reports', [BugReportController::class, 'index'])->name('bug-reports.index');
    Route::get('bug-reports/create', [BugReportController::class, 'create'])->name('bug-reports.create');
    Route::post('bug-reports', [BugReportController::class, 'store'])->name('bug-reports.store');
    Route::post('bug-reports/{bugReport}/approve', [BugReportReviewController::class, 'approve'])->name('bug-reports.approve');
    Route::post('bug-reports/{bugReport}/escalate', [BugReportReviewController::class, 'escalate'])->name('bug-reports.escalate');
    Route::post('bug-reports/{bugReport}/reject', [BugReportReviewController::class, 'reject'])->name('bug-reports.reject');

    Route::get('need-approval', [NeedApprovalController::class, 'index'])->name('need-approval.index');

    Route::get('kpi', [KpiController::class, 'index'])->name('kpi.index');
    Route::post('kpi/periods', [KpiController::class, 'close'])->name('kpi.periods.close');
    Route::get('kpi/{user}', [KpiController::class, 'show'])->name('kpi.show');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/holidays', [SettingController::class, 'storeHoliday'])->name('settings.holidays.store');
    Route::post('settings/holidays/sync', [SettingController::class, 'syncHolidays'])->name('settings.holidays.sync');
    Route::delete('settings/holidays/{holiday}', [SettingController::class, 'destroyHoliday'])->name('settings.holidays.destroy');

    Route::resource('users', UserController::class)->except('show');
});
