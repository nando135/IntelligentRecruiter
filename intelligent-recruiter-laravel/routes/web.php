<?php

use App\Http\Controllers\ApprovedCandidateController;
use App\Http\Controllers\CandidateApiController;
use App\Http\Controllers\CandidateApprovalController;
use App\Http\Controllers\CandidateUploadController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;

// ─── Blade Routes ─────────────────────────────────────────────────────────

Route::get('/', function () {
    return redirect()->route('candidates.index');
});

Route::get('/candidates', [CandidateUploadController::class, 'index'])
    ->name('candidates.index');

Route::get('/candidates/upload', [CandidateUploadController::class, 'create'])
    ->name('candidates.upload');

Route::post('/candidates/upload', [CandidateUploadController::class, 'store'])
    ->name('candidates.store');

Route::post('/candidates/bulk-approve', [CandidateApprovalController::class, 'bulkApprove'])
    ->name('candidates.bulk-approve');

Route::post('/candidates/{candidate}/approve', [CandidateApprovalController::class, 'approve'])
    ->name('candidates.approve');

Route::get('/candidates/{candidate}', [CandidateUploadController::class, 'show'])
    ->name('candidates.show');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])
    ->name('leaderboard.index');

Route::post('/leaderboard/rank-by-job', [LeaderboardController::class, 'rankByJob'])
    ->name('leaderboard.rank-by-job');

Route::get('/approved-candidates', [ApprovedCandidateController::class, 'index'])
    ->name('approved-candidates.index');

Route::get('/email-templates', [EmailTemplateController::class, 'index'])
    ->name('email-templates.index');

Route::get('/email-templates/create', [EmailTemplateController::class, 'create'])
    ->name('email-templates.create');

Route::post('/email-templates', [EmailTemplateController::class, 'store'])
    ->name('email-templates.store');

Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])
    ->name('email-templates.edit');

Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])
    ->name('email-templates.update');

Route::post('/email-templates/{emailTemplate}/set-active', [EmailTemplateController::class, 'setActive'])
    ->name('email-templates.set-active');

// ─── API / Debug Routes ───────────────────────────────────────────────────

Route::prefix('api')->group(function () {
    Route::get('/candidates', [CandidateApiController::class, 'index']);
    Route::get('/candidates/{candidate}', [CandidateApiController::class, 'show']);
    Route::get('/candidates/{candidate}/parsed-json', [CandidateApiController::class, 'parsedJson']);
    Route::get('/candidates/{candidate}/raw-text', [CandidateApiController::class, 'rawText']);
    Route::get('/candidates/{candidate}/experiences', [CandidateApiController::class, 'experiences']);
    Route::get('/candidates/{candidate}/skills', [CandidateApiController::class, 'skills']);
});
