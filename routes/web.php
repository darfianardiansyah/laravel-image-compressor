<?php

use App\Http\Controllers\ImageConversionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ImageConversionController::class, 'index'])->name('conversions.index');
Route::post('/convert', [ImageConversionController::class, 'convert'])
    ->middleware('throttle:10,1')
    ->name('conversions.convert');
Route::get('/download/{filename}', [ImageConversionController::class, 'download'])
    ->where('filename', '[A-Za-z0-9\-]+\.(webp|avif)')
    ->name('conversions.download');
