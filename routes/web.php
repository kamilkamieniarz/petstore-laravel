<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('pets', PetController::class);
Route::get('/pets-search', [PetController::class, 'search'])->name('pets.search');