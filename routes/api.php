<?php

use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public
    Route::post('/otp/send', [AuthController::class, 'sendOtp'])->middleware('throttle:5,15');
    Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,15');
    Route::post('/magic-link/verify', [AuthController::class, 'verifyMagicLink'])->middleware('throttle:20,15');
    Route::post('/magic-link/resend', [AuthController::class, 'resendMagicLink'])->middleware('throttle:5,15');

    // Protected (staff only)
    Route::middleware(['auth:sanctum', 'ability:admin,recruiter,author'])->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // Questions
        Route::apiResource('questions', QuestionController::class);

        // Tests
        Route::apiResource('tests', TestController::class);
        Route::post('/tests/{test}/publish', [TestController::class, 'publish']);
        Route::post('/tests/{test}/archive', [TestController::class, 'archive']);
        // Section management
        Route::post('/tests/{test}/sections', [TestController::class, 'addSection']);
        Route::put('/tests/{test}/sections/{section}', [TestController::class, 'updateSection']);
        Route::delete('/tests/{test}/sections/{section}', [TestController::class, 'deleteSection']);
        Route::post('/sections/{section}/questions', [TestController::class, 'attachQuestion']);
        Route::put('/sections/{section}/questions/{question}', [TestController::class, 'updateQuestionPivot']);
        Route::delete('/sections/{section}/questions/{question}', [TestController::class, 'detachQuestion']);

        // Invitations
        Route::apiResource('invitations', InvitationController::class);
        Route::post('/invitations/bulk', [InvitationController::class, 'bulkStore']);

        // Admin: Users
        Route::middleware('can:manage-users')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::put('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        });

        // CSV Import
        Route::post('/imports/questions', [ImportController::class, 'importQuestions']);
        Route::get('/imports/{import}', [ImportController::class, 'show']);

        // Tags
        Route::apiResource('tags', TagController::class)->except(['show']); // full CRUD for admin/author

        // Organization settings (admin only)
        Route::get('/organization', [OrganizationController::class, 'show']);
        Route::put('/organization', [OrganizationController::class, 'update'])->middleware('can:manage-organization');
    });

    // Attempts (candidate + staff view)
    Route::middleware(['auth:sanctum', 'ability:candidate,admin,recruiter'])->group(function () {
        Route::get('/attempts/{attempt}', [AttemptController::class, 'show']);
    });

    // Candidate attempt lifecycle
    Route::middleware(['auth:sanctum', 'ability:candidate'])->group(function () {
        Route::post('/attempts/{attempt}/start', [AttemptController::class, 'start']);
        Route::put('/attempts/{attempt}', [AttemptController::class, 'update']);
        Route::post('/attempts/{attempt}/submit', [AttemptController::class, 'submit']);
    });

    // Reports and grading (recruiter/admin only)
    Route::middleware(['auth:sanctum', 'ability:admin,recruiter'])->group(function () {
        Route::post('/attempts/{attempt}/grade', [AttemptController::class, 'grade'])->middleware('can:grade,attempt');
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/attempt/{attempt}', [ReportController::class, 'show']);
        Route::get('/reports/attempt/{attempt}/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('/reports/attempt/{attempt}/csv', [ReportController::class, 'exportCsv']);
    });
});
