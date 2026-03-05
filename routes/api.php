<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);

    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);

    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});
