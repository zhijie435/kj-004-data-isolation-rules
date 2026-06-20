<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => '在线课程教务系统 API',
        'version' => '1.0.0',
    ]);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('data-isolation')->group(function () {
        Route::get('/rules', [\App\Http\Controllers\Api\DataIsolationRuleController::class, 'index']);
        Route::post('/rules', [\App\Http\Controllers\Api\DataIsolationRuleController::class, 'store']);
        Route::get('/rules/{rule}', [\App\Http\Controllers\Api\DataIsolationRuleController::class, 'show']);
        Route::put('/rules/{rule}', [\App\Http\Controllers\Api\DataIsolationRuleController::class, 'update']);
        Route::delete('/rules/{rule}', [\App\Http\Controllers\Api\DataIsolationRuleController::class, 'destroy']);
        Route::get('/rule-types', [\App\Http\Controllers\Api\DataIsolationRuleController::class, 'getRuleTypes']);
        Route::post('/test-rule', [\App\Http\Controllers\Api\DataIsolationRuleController::class, 'testRule']);
    });

    Route::get('/courses', [\App\Http\Controllers\Api\CourseController::class, 'index']);
    Route::get('/classes', [\App\Http\Controllers\Api\ClassController::class, 'index']);
});
