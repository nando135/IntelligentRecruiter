<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('chat')->group(function () {
    Route::post('/new-thread',           [ChatController::class, 'newThread']);
    Route::post('/send',                 [ChatController::class, 'send']);
    Route::get('/history/{thread_id}',   [ChatController::class, 'history']);
    Route::get('/threads',               [ChatController::class, 'threads']);
    Route::delete('/thread/{thread_id}', [ChatController::class, 'deleteThread']);
    Route::get('/stats',                 [ChatController::class, 'stats']);
});