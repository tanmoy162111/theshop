<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/agent')->group(function () {
    Route::post('register', [AgentController::class, 'register'])->middleware('throttle:20,1');
    Route::middleware('agent.auth')->group(function () {
        Route::post('report', [AgentController::class, 'report']);
        Route::get('status', [AgentController::class, 'status']);
    });
});
