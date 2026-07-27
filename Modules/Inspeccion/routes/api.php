<?php

use Illuminate\Support\Facades\Route;
use Modules\Inspeccion\Http\Controllers\InspeccionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('inspeccions', InspeccionController::class)->names('inspeccion');
});
