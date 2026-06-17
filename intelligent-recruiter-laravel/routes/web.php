<?php

use App\Http\Controllers\ApprovedCandidateController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateApiController;
use App\Http\Controllers\CandidateApprovalController;
use App\Http\Controllers\CandidateUploadController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;

// ─── Auth Routes (public) ─────────────────────────────────────────────────

Route::get('/login', fn () => view('auth.login'))->name('login')->middleware('guest');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Protected Routes ─────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('candidates.index'));

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

});

// ─── Chat Routes (session auth) ───────────────────────────────────────────

Route::prefix('api/chat')->middleware('auth')->group(function () {
    Route::post('/new-thread',           [ChatController::class, 'newThread']);
    Route::post('/send',                 [ChatController::class, 'send']);
    Route::get('/history/{thread_id}',   [ChatController::class, 'history']);
    Route::get('/threads',               [ChatController::class, 'threads']);
    Route::delete('/thread/{thread_id}', [ChatController::class, 'deleteThread']);
    Route::get('/stats',                 [ChatController::class, 'stats']);
});

// ─── API / Debug Routes ───────────────────────────────────────────────────

Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/candidates', [CandidateApiController::class, 'index']);
    Route::get('/candidates/{candidate}', [CandidateApiController::class, 'show']);
    Route::get('/candidates/{candidate}/parsed-json', [CandidateApiController::class, 'parsedJson']);
    Route::get('/candidates/{candidate}/raw-text', [CandidateApiController::class, 'rawText']);
    Route::get('/candidates/{candidate}/experiences', [CandidateApiController::class, 'experiences']);
    Route::get('/candidates/{candidate}/skills', [CandidateApiController::class, 'skills']);
});
