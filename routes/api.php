<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Department;

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

// Get departments for a specific company
Route::get('/companies/{companyId}/departments', function ($companyId) {
    return Department::where('company_id', $companyId)
        ->where('is_active', true)
        ->orderBy('name')
        ->select('id', 'name')
        ->get();
});
