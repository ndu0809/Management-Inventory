
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ItemController;

// Login tidak memerlukan token
Route::post('/login', [AuthController::class, 'login']);

// Endpoint yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {

    // Mendapatkan data user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout dan menghapus token yang sedang digunakan
    Route::post('/logout', [AuthController::class, 'logout']);

    // CRUD anggota
    Route::apiResource('members', MemberController::class);

    // CRUD inventaris
    Route::apiResource('items', ItemController::class);
});
