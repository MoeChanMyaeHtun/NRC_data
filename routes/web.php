<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NrcController;

Route::get('/', [NrcController::class, 'create'])->name('nrc.create');
Route::post('/nrc', [NrcController::class, 'store'])->name('nrc.store');