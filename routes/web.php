<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfToTextController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



Route::get('/', function () {
    return view('request');
});


Route::get('/upload', function () {
    return view('request');
});

Route::post('/convert', [PdfToTextController::class, 'convert'])->name('convert');

Route::get('/result', function () {
    return view('watch');
});

Route::post('/convert/png', [PdfToTextController::class, 'convertPng'])->name('convert.png');

Route::post('/upload', [PdfToTextController::class, 'upload'])->name('upload');

Route::post('/search', [SearchController::class, 'search'])->name('search');

Route::get('/question', function () {
    return view('question');
})->name('question');

Route::get('/request', function () {
    return view('request');
})->name('request');

Route::get('/response/{id}', [SearchController::class, 'response'])->name('response');
