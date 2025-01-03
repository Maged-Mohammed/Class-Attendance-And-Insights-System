<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware (['auth:sanctum'])->get ('/user', function (Request $request) {
    return $request->user ();
});

Route::post ('/register', [\App\Http\Controllers\authController::class, 'register']);
Route::post ('/login', [\App\Http\Controllers\authController::class, 'login']);

Route::middleware ('auth:sanctum')->group (function () {
    Route::post ('/add-classes', [\App\Http\Controllers\ClassController::class, 'store']);
    Route::get ('/get-classes', [\App\Http\Controllers\ClassController::class, 'getClasses']);
    
    Route::post ('/add-student', [\App\Http\Controllers\StudentController::class, 'store']);
    Route::post ('/add-attendance', [\App\Http\Controllers\AttendanceController::class, 'store']);
    Route::get ('/attendance/class/{class_id}', [\App\Http\Controllers\AttendanceController::class, 'getClassAttendance']);
    Route::get ('/report', [\App\Http\Controllers\AttendanceController::class, 'getReport']);
    
});
