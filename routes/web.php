<?php

use Illuminate\Support\Facades\Route;

Route::view('/members', 'members');
Route::get('/', function () {
    return view('welcome');
});
