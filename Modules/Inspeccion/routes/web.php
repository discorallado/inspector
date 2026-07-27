<?php

use Illuminate\Support\Facades\Route;
use Modules\Inspeccion\Http\Controllers\InspeccionController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('inspeccions', InspeccionController::class)->names('inspeccion');
});
