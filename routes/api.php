<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\StateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/device')
    ->middleware(['device.token', 'throttle:60,1'])
    ->group(function () {
        Route::post('events', [EventController::class, 'store']);
        Route::put('goal', [GoalController::class, 'update']);
        Route::get('state', [StateController::class, 'show']);
    });
